#!/usr/bin/env bash
set -euo pipefail

echo "[init] Creating SQS queues..."
awslocal sqs create-queue --queue-name default || true
awslocal sqs create-queue --queue-name notifications-queue || true
awslocal sqs list-queues || true
echo "[init] done."
