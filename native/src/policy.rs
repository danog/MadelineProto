// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
#[derive(Default)]
pub struct Policy {
    pub width: usize,
    pub locked: bool,
    turn: usize,
    samples: [Vec<f64>; 2],
}
impl Policy {
    pub fn target(&self) -> usize {
        self.width.max(1) + if self.locked { 0 } else { self.turn }
    }
    pub fn checks(larger: usize, gain: f64) -> usize {
        (3.0 * larger as f64 * (2.0 / (gain - 1.0).abs().max(0.25)).clamp(1.0, 8.0)).ceil() as usize
    }
    pub fn observe(&mut self, width: usize, bytes: usize, cpu_ns: u64, ceiling: usize) {
        self.width = self.width.max(1);
        if self.locked || bytes == 0 || width != self.target() {
            return;
        }
        if self.width >= ceiling {
            self.locked = true;
            return;
        }
        self.samples[self.turn].push(cpu_ns as f64 / bytes as f64);
        self.turn ^= 1;
        let base = 3 * (self.width + 1);
        if self.samples.iter().any(|s| s.len() < base) {
            return;
        }
        let mut median = |i: usize| {
            let s = &mut self.samples[i];
            s.sort_by(f64::total_cmp);
            if s.len() % 2 == 1 {
                s[s.len() / 2]
            } else {
                (s[s.len() / 2 - 1] + s[s.len() / 2]) / 2.0
            }
        };
        let lower = median(0);
        let upper = median(1);
        let gain = if lower > 0.0 {
            100.0 * (lower - upper) / lower
        } else {
            0.0
        };
        let required = Self::checks(self.width + 1, gain);
        if self.samples.iter().any(|s| s.len() < required) {
            return;
        }
        if gain >= 1.0 {
            self.width += 1;
            self.turn = 0;
            self.locked = self.width == ceiling;
        } else {
            self.locked = true;
        }
        self.samples = Default::default();
    }
}
