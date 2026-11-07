#!/bin/bash
# Deploy current branch to DreamHost via git push + ssh pull
# (mc's counterpart to mg.robnugen.com/deploy_on_dh.sh)
set -e

cd "$(dirname "$0")"
BRANCH=$(git rev-parse --abbrev-ref HEAD)

# Locally the GitHub remote is "gorigin"; on DreamHost it is "origin".
echo "Pushing $BRANCH to gorigin..."
git push -u gorigin "$BRANCH"

# Refuse to deploy over a dirty server tree instead of `git restore .`:
# the live app moves files (api/delete-curve.php moves curves/ into
# curves_deleted/), and restoring would silently undo those deletions.
# Commit them on DH and bring them into the branch first.
DIRTY=$(ssh mc "cd mc.robnugen.com && git status --porcelain")
if [ -n "$DIRTY" ]; then
    echo "DreamHost working tree is not clean; not deploying:" >&2
    echo "$DIRTY" >&2
    exit 1
fi

# checkout + --ff-only puts DH on exactly this branch instead of
# merging it into whatever branch DH happens to have checked out.
# pack.threads=1: DreamHost's process limit kills multithreaded packing.
echo "Pulling $BRANCH on DreamHost..."
ssh mc "cd mc.robnugen.com && git -c pack.threads=1 fetch origin && git checkout $BRANCH && git pull --ff-only origin $BRANCH"

echo "Deployed $BRANCH to DreamHost."
