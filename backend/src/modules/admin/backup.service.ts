import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import fs from 'node:fs/promises';
import path from 'node:path';
import { config } from '@/config/env';
import { prisma } from '@/config/prisma';
import { BadRequest, HttpError, NotFound } from '@/utils/httpError';
import { logger } from '@/utils/logger';
import { setSetting } from '@/services/systemSettings.service';

const execFileAsync = promisify(execFile);

const BACKUP_DIR = process.env.BACKUP_DIR || path.resolve(process.cwd(), 'backups');
const FILENAME_RE = /^backup_[\w.-]+\.sql$/;
const MAX_FILES = 10;
const DUMP_TIMEOUT_MS = 120_000;

export interface BackupFileInfo {
  filename: string;
  sizeBytes: number;
  createdAt: string;
}

function parseDatabaseUrl(databaseUrl: string) {
  const parsed = new URL(databaseUrl);
  const database = decodeURIComponent(parsed.pathname.replace(/^\//, '').split('?')[0] ?? '');
  if (!database) {
    throw new HttpError(500, 'BACKUP_FAILED', 'DATABASE_URL is missing a database name');
  }
  return {
    host: parsed.hostname,
    port: parsed.port || '5432',
    user: decodeURIComponent(parsed.username),
    password: decodeURIComponent(parsed.password),
    database,
  };
}

async function resolvePgDump(): Promise<string> {
  const candidates = [
    process.env.PG_DUMP_PATH,
    'pg_dump',
    'C:\\Program Files\\PostgreSQL\\16\\bin\\pg_dump.exe',
    'C:\\Program Files\\PostgreSQL\\15\\bin\\pg_dump.exe',
    '/usr/bin/pg_dump',
  ].filter((value): value is string => Boolean(value));

  for (const candidate of candidates) {
    try {
      await execFileAsync(candidate, ['--version'], { timeout: 5_000, windowsHide: true });
      return candidate;
    } catch {
      continue;
    }
  }

  throw new HttpError(500, 'BACKUP_UNAVAILABLE', 'pg_dump was not found on this server');
}

async function ensureBackupDir() {
  await fs.mkdir(BACKUP_DIR, { recursive: true });
}

function backupPath(filename: string) {
  if (!FILENAME_RE.test(filename)) throw BadRequest('Invalid backup filename');
  return path.join(BACKUP_DIR, filename);
}

async function cleanupOldBackups() {
  const files = await listBackupFiles();
  const retentionRaw = await prisma.systemSetting.findUnique({ where: { settingKey: 'backup_retention_days' } });
  const retentionDays = Math.max(1, Number(retentionRaw?.settingValue ?? 30) || 30);
  const cutoff = Date.now() - retentionDays * 24 * 60 * 60 * 1000;

  for (const file of files) {
    if (new Date(file.createdAt).getTime() < cutoff) {
      await fs.unlink(backupPath(file.filename)).catch(() => undefined);
    }
  }

  const remaining = await listBackupFiles();
  for (const file of remaining.slice(MAX_FILES)) {
    await fs.unlink(backupPath(file.filename)).catch(() => undefined);
  }
}

async function listBackupFiles(): Promise<BackupFileInfo[]> {
  await ensureBackupDir();
  const entries = await fs.readdir(BACKUP_DIR);
  const files: BackupFileInfo[] = [];

  for (const filename of entries) {
    if (!FILENAME_RE.test(filename)) continue;
    const stat = await fs.stat(path.join(BACKUP_DIR, filename));
    if (!stat.isFile()) continue;
    files.push({ filename, sizeBytes: stat.size, createdAt: stat.mtime.toISOString() });
  }

  return files.sort((a, b) => b.createdAt.localeCompare(a.createdAt));
}

export async function listBackups() {
  const files = await listBackupFiles();
  const last = await prisma.systemSetting.findUnique({ where: { settingKey: 'last_backup' } });
  const schedule = await prisma.systemSetting.findUnique({ where: { settingKey: 'backup_schedule' } });
  const retention = await prisma.systemSetting.findUnique({ where: { settingKey: 'backup_retention_days' } });
  return {
    files,
    lastBackupAt: last?.settingValue ?? null,
    schedule: schedule?.settingValue ?? '02:00 Africa/Kigali',
    retentionDays: Number(retention?.settingValue ?? 30) || 30,
  };
}

export async function triggerBackup(staffId: string) {
  const pgDump = await resolvePgDump();
  const db = parseDatabaseUrl(config.databaseUrl);
  await ensureBackupDir();

  const now = new Date();
  const pad = (value: number) => String(value).padStart(2, '0');
  const timestamp = `${now.getUTCFullYear()}${pad(now.getUTCMonth() + 1)}${pad(now.getUTCDate())}_${pad(now.getUTCHours())}${pad(now.getUTCMinutes())}${pad(now.getUTCSeconds())}`;
  const filename = `backup_${db.database}_${timestamp}.sql`;
  const filePath = backupPath(filename);

  try {
    await execFileAsync(
      pgDump,
      [
        '-h',
        db.host,
        '-p',
        db.port,
        '-U',
        db.user,
        '-d',
        db.database,
        '--no-owner',
        '--no-acl',
        '-F',
        'p',
        '-f',
        filePath,
      ],
      {
        env: { ...process.env, PGPASSWORD: db.password },
        timeout: DUMP_TIMEOUT_MS,
        windowsHide: true,
        maxBuffer: 10 * 1024 * 1024,
      },
    );
  } catch (err) {
    await fs.unlink(filePath).catch(() => undefined);
    logger.error({ err }, 'pg_dump failed');
    throw new HttpError(500, 'BACKUP_FAILED', 'Database backup failed');
  }

  const stat = await fs.stat(filePath).catch(() => null);
  if (!stat || stat.size === 0) {
    await fs.unlink(filePath).catch(() => undefined);
    throw new HttpError(500, 'BACKUP_FAILED', 'Database backup produced an empty file');
  }

  const completedAt = new Date().toISOString();
  await setSetting('last_backup', completedAt, 'Timestamp of last successful backup');
  await prisma.auditTrail.create({
    data: {
      staffId,
      actionType: 'trigger_backup',
      tableName: 'system_settings',
      newValue: filename,
      reason: `Database backup completed (${stat.size} bytes)`,
    },
  });
  await cleanupOldBackups();

  logger.info({ filename, sizeBytes: stat.size }, 'Database backup completed');
  return { filename, sizeBytes: stat.size, createdAt: completedAt };
}

export async function resolveBackupFile(filename: string) {
  const filePath = backupPath(filename);
  try {
    await fs.access(filePath);
  } catch {
    throw NotFound('Backup file not found');
  }
  return filePath;
}
