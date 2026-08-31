import { describe, expect, it } from 'vitest';
import { featuresList, planHasFeature } from '@/modules/subscriptions/subscription.service';

describe('plan feature settings', () => {
  it('reads a JSON string array and ignores junk', () => {
    expect(featuresList(['qr_ordering', 'kitchen_display', 12, null, 'analytics'])).toEqual([
      'qr_ordering',
      'kitchen_display',
      'analytics',
    ]);
    expect(featuresList(null)).toEqual([]);
    expect(featuresList({ kitchen_display: true })).toEqual([]);
  });

  it('checks inclusion against the stored plan settings', () => {
    const features = ['basic_pos', 'qr_ordering'];
    expect(planHasFeature(features, 'qr_ordering')).toBe(true);
    expect(planHasFeature(features, 'kitchen_display')).toBe(false);
    expect(planHasFeature(features, 'analytics')).toBe(false);
  });
});
