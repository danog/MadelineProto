// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
#![cfg_attr(not(feature = "php-extension"), allow(dead_code))]
mod crypto;
mod executor;
#[cfg(any(target_arch = "aarch64", target_arch = "x86_64"))]
mod hardware;
#[cfg(feature = "php-extension")]
mod php;
mod policy;
#[cfg(test)]
mod tests;
