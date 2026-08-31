import { config } from '@/config/env';
import { logger } from '@/utils/logger';
import { startRealtimeListener } from '@/services/realtime.service';
import { createApp } from './app';

const app = createApp();

app.listen(config.port, () => {
  logger.info(`Smart Restaurant API listening on port ${config.port} [${config.nodeEnv}]`);
  void startRealtimeListener();
});
