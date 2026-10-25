# Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
# SPDX-License-Identifier: AGPL-3.0-only
cmake_minimum_required(VERSION 3.16)
project(madeline_ige LANGUAGES C)
find_package(OpenSSL 1.1 REQUIRED COMPONENTS Crypto)
add_library(madeline-ige SHARED ige.c)
target_compile_features(madeline-ige PRIVATE c_std_99)
target_link_libraries(madeline-ige PRIVATE OpenSSL::Crypto)
set_target_properties(madeline-ige PROPERTIES WINDOWS_EXPORT_ALL_SYMBOLS ON)
