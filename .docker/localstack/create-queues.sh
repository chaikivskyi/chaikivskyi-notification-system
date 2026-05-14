#!/usr/bin/env bash
set -euo pipefail

echo "[init] Creating SQS queues..."
awslocal sqs create-queue --queue-name default || true
awslocal sqs create-queue --queue-name notifications-high || true
awslocal sqs create-queue --queue-name notifications-normal || true
awslocal sqs create-queue --queue-name notifications-low || true
awslocal sqs list-queues || true
echo "[init] done."
