---
name: skill-git
description: "**WORKFLOW SKILL** — Professional Git command management at expert level. USE FOR: complete Git workflows (GitFlow, GitHub Flow, trunk-based, release branches); branching strategies; commit conventions (Conventional Commits, semantic versioning); rebasing, cherry-picking, bisect, reflog recovery; stash management; tagging and releases; submodules and subtrees; hooks (pre-commit, commit-msg, pre-push); conflict resolution strategies; history rewriting (interactive rebase, filter-branch, filter-repo); remote management (multiple remotes, fetch, pull, push strategies); worktrees; sparse checkout; Git internals; CI/CD integration; monorepo Git strategies; large file handling (Git LFS); undoing mistakes; repository maintenance and optimization. INVOKES: terminal tools, file system tools. DO NOT USE FOR: GitHub/GitLab API management; CI/CD pipeline configuration unrelated to Git; project management outside version control."
---

# Git — Professional Command Management

## Core Philosophy

- **History is sacred**: Never rewrite shared history on public/shared branches without explicit team consensus.
- **Commits are units of meaning**: Each commit should be atomic, reversible, and self-explanatory.
- **Branches are cheap**: Use them liberally; delete aggressively after merging.
- **Always know your state**: `git status`, `git log`, `git diff` before any destructive operation.
- **Recover before panic**: Almost everything in Git is recoverable via `reflog`.

---

## Daily Workflow Commands

### Status & Inspection

```bash
# Full status with short format
git status -sb

# See what changed (unstaged)
git diff

# See what is staged
git diff --cached

# See diff for a specific file
git diff HEAD -- path/to/file.php

# Last commit details
git show --stat

# Full log — graph view
git log --oneline --graph --decorate --all

# Search commits by message
git log --oneline --grep="fix"

# Search commits that introduced a string
git log -S "functionName" --oneline

# Who changed what in a file
git log --follow -p -- path/to/file.php

# Blame with date and abbreviated hash
git blame -w -C -C -C --date=short path/to/file.php
```

### Staging & Committing

```bash
# Stage specific file
git add path/to/file.php

# Stage parts of a file interactively (hunk by hunk)
git add -p path/to/file.php

# Stage all tracked changes (never use in a hurry)
git add -u

# Commit with message
git commit -m "feat(auth): add OAuth2 login flow"

# Commit and open editor for long message
git commit

# Amend last commit message (only if not pushed)
git commit --amend --only -m "corrected message"

# Amend last commit adding staged changes (only if not pushed)
git commit --amend --no-edit

# Empty commit (useful for triggering CI)
git commit --allow-empty -m "ci: trigger pipeline"
```

### Undoing Changes

```bash
# Unstage a file (keeps changes in working tree)
git restore --staged path/to/file.php

# Discard working tree changes for a file (destructive)
git restore path/to/file.php

# Undo last commit, keep changes staged
git reset --soft HEAD~1

# Undo last commit, keep changes unstaged
git reset --mixed HEAD~1

# Undo last commit completely (destructive — only local)
git reset --hard HEAD~1

# Create a new commit that reverts a past commit (safe for shared branches)
git revert <commit-sha>

# Revert multiple commits (oldest first)
git revert <oldest-sha>..<newest-sha>

# Revert a merge commit
git revert -m 1 <merge-commit-sha>
```

---

## Branching Strategies

### GitFlow

```
main          ← production-ready, tagged releases
develop       ← integration branch
feature/*     ← new features, branched from develop
release/*     ← pre-release stabilization, branched from develop
hotfix/*      ← urgent fixes, branched from main
```

```bash
# Start a feature
git checkout -b feature/user-authentication develop

# Finish a feature (merge back to develop, no fast-forward)
git checkout develop
git merge --no-ff feature/user-authentication
git branch -d feature/user-authentication

# Start a release
git checkout -b release/1.4.0 develop

# Finish a release
git checkout main
git merge --no-ff release/1.4.0
git tag -a v1.4.0 -m "Release 1.4.0"
git checkout develop
git merge --no-ff release/1.4.0
git branch -d release/1.4.0

# Start a hotfix
git checkout -b hotfix/fix-payment-crash main

# Finish a hotfix
git checkout main
git merge --no-ff hotfix/fix-payment-crash
git tag -a v1.4.1 -m "Hotfix 1.4.1"
git checkout develop
git merge --no-ff hotfix/fix-payment-crash
git branch -d hotfix/fix-payment-crash
```

### GitHub Flow (Simplified)

```
main          ← always deployable
feature/*     ← every change via PR to main
```

```bash
# New branch from main
git checkout -b feature/add-search main

# Work, commit, push
git push -u origin feature/add-search

# Merge via PR; after merge, clean up
git checkout main
git pull origin main
git branch -d feature/add-search
git push origin --delete feature/add-search
```

### Trunk-Based Development

```
main          ← single integration branch, deployed continuously
feature/*     ← short-lived (< 2 days), merged via PR with squash
```

```bash
# Short-lived branch
git checkout -b feat/quick-fix main

# Squash merge to keep trunk history clean
git checkout main
git merge --squash feat/quick-fix
git commit -m "feat: quick fix description"
git branch -d feat/quick-fix
```

---

## Rebase Workflows

```bash
# Rebase current branch on top of main (linear history)
git fetch origin
git rebase origin/main

# Interactive rebase — edit last 5 commits
git rebase -i HEAD~5

# Interactive rebase commands:
# pick   — keep commit as-is
# reword — keep commit, edit message
# edit   — keep commit, pause to amend
# squash — merge into previous commit, combine messages
# fixup  — merge into previous commit, discard message
# drop   — delete commit entirely
# exec   — run shell command after commit

# Rebase and auto-squash fixup commits
git rebase -i --autosquash origin/main

# Create a fixup commit for a specific past commit
git add -p
git commit --fixup=<sha-of-commit-to-fix>

# Abort a rebase in progress
git rebase --abort

# Continue after resolving conflicts
git add path/to/resolved-file.php
git rebase --continue

# Skip a conflicting commit during rebase
git rebase --skip
```

---

## Cherry-Pick

```bash
# Apply a single commit to current branch
git cherry-pick <sha>

# Apply a range of commits
git cherry-pick <from-sha>..<to-sha>

# Cherry-pick without committing (stage only)
git cherry-pick -n <sha>

# Cherry-pick a merge commit (specify parent)
git cherry-pick -m 1 <merge-sha>

# Continue after conflict resolution
git cherry-pick --continue

# Abort
git cherry-pick --abort
```

---

## Stash Management

```bash
# Stash current changes with a descriptive name
git stash push -m "WIP: refactoring auth middleware"

# Stash including untracked files
git stash push -u -m "WIP: new feature files"

# List all stashes
git stash list

# Apply most recent stash (keeps it in list)
git stash apply

# Apply specific stash
git stash apply stash@{2}

# Pop most recent stash (apply + drop)
git stash pop

# See what is in a stash
git stash show -p stash@{0}

# Drop a specific stash
git stash drop stash@{1}

# Clear all stashes
git stash clear

# Create a branch from a stash
git stash branch feature/recover-from-stash stash@{0}
```

---

## Remote Management

```bash
# List remotes with URLs
git remote -v

# Add a remote
git remote add upstream https://github.com/org/repo.git

# Change remote URL
git remote set-url origin git@github.com:user/repo.git

# Fetch all remotes
git fetch --all --prune

# Pull with rebase (preferred over merge pull)
git pull --rebase origin main

# Push and set upstream in one step
git push -u origin feature/my-branch

# Force push safely (only moves forward — rejects if remote has new commits)
git push --force-with-lease origin feature/my-branch

# Delete remote branch
git push origin --delete feature/old-branch

# Prune stale remote-tracking branches
git remote prune origin

# Sync fork with upstream
git fetch upstream
git checkout main
git merge upstream/main
git push origin main
```

---

## Tagging & Releases

```bash
# Create annotated tag (always prefer over lightweight)
git tag -a v2.1.0 -m "Release 2.1.0 — adds payment module"

# Tag a specific commit
git tag -a v2.0.1 <sha> -m "Hotfix 2.0.1"

# Push a single tag
git push origin v2.1.0

# Push all tags
git push origin --tags

# List tags sorted by version
git tag --sort=-version:refname

# Delete local tag
git tag -d v2.1.0

# Delete remote tag
git push origin --delete v2.1.0

# Show tag details
git show v2.1.0
```

---

## Conflict Resolution

```bash
# See all conflicted files
git diff --name-only --diff-filter=U

# Use a merge tool
git mergetool

# Accept ours entirely for a file
git checkout --ours path/to/file.php
git add path/to/file.php

# Accept theirs entirely for a file
git checkout --theirs path/to/file.php
git add path/to/file.php

# View 3-way diff during conflict
git diff --cc path/to/file.php

# After all conflicts resolved, complete the merge
git commit

# Abort a merge
git merge --abort
```

### Conflict Markers Reference

```
<<<<<<< HEAD (ours)
    // our version of the code
=======
    // their version of the code
>>>>>>> feature/other-branch (theirs)
```

---

## History Rewriting

> **Warning**: Only rewrite commits that have NOT been pushed to a shared remote.

```bash
# Interactive rebase to clean up last N commits before pushing
git rebase -i HEAD~N

# Change author on last commit
git commit --amend --author="Name <email@example.com>"

# Change author across multiple commits (git filter-repo — install separately)
git filter-repo --commit-callback '
  if commit.author_email == b"old@email.com":
      commit.author_email = b"new@email.com"
      commit.author_name = b"New Name"
'

# Remove a file from entire history (git filter-repo)
git filter-repo --path secrets.env --invert-paths

# Split a commit into multiple (during interactive rebase, mark as 'edit')
# Then:
git reset HEAD~1             # unstage the commit
git add -p                   # stage first logical change
git commit -m "first part"
git add -p                   # stage second logical change
git commit -m "second part"
git rebase --continue
```

---

## Bisect — Find the Commit that Introduced a Bug

```bash
# Start bisect
git bisect start

# Mark current state as bad
git bisect bad

# Mark a known good commit
git bisect good v1.3.0

# Git checks out the midpoint automatically — test it, then mark:
git bisect good   # or
git bisect bad

# Git narrows down until it finds the culprit commit
# End session
git bisect reset

# Automate with a test script (exit 0 = good, exit 1 = bad)
git bisect run php tests/regression.php
```

---

## Reflog — Recovery

```bash
# See full reflog (every HEAD movement)
git reflog

# See reflog for a specific branch
git reflog show feature/lost-work

# Recover a dropped commit
git checkout -b recovery/lost-commit <sha-from-reflog>

# Recover after accidental reset --hard
git reset --hard <sha-from-reflog>

# Show reflog with dates
git reflog --date=iso

# Expire old reflog entries (maintenance)
git reflog expire --expire=90.days.ago --all
```

---

## Worktrees

```bash
# Add a worktree for a branch (work on two branches simultaneously)
git worktree add ../hotfix-tree hotfix/payment-crash

# List worktrees
git worktree list

# Remove a worktree
git worktree remove ../hotfix-tree

# Prune stale worktree references
git worktree prune
```

---

## Submodules

```bash
# Add a submodule
git submodule add https://github.com/org/lib.git libs/lib

# Clone repo with all submodules
git clone --recurse-submodules https://github.com/org/repo.git

# Initialize and update submodules after clone
git submodule update --init --recursive

# Update all submodules to latest remote commit
git submodule update --remote --merge

# Run a command in each submodule
git submodule foreach 'git pull origin main'

# Remove a submodule
git submodule deinit libs/lib
git rm libs/lib
rm -rf .git/modules/libs/lib
```

---

## Git Hooks

Location: `.git/hooks/` (local) or shared via `.githooks/` + config.

```bash
# Tell Git to use a shared hooks directory
git config core.hooksPath .githooks
```

### pre-commit (lint + type check before commit)

```bash
#!/usr/bin/env bash
set -e

echo "Running pre-commit checks..."

# PHP syntax check on staged files
STAGED=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' || true)
if [ -n "$STAGED" ]; then
    echo "$STAGED" | xargs -I{} php -l {}
    ./vendor/bin/phpstan analyse $STAGED --level=8 --no-progress
fi

# JS/TS lint
if git diff --cached --name-only | grep -qE '\.(js|ts|tsx)$'; then
    npx eslint --fix-dry-run $(git diff --cached --name-only | grep -E '\.(js|ts|tsx)$')
fi
```

### commit-msg (Conventional Commits enforcement)

```bash
#!/usr/bin/env bash
COMMIT_MSG_FILE=$1
COMMIT_MSG=$(cat "$COMMIT_MSG_FILE")

PATTERN='^(feat|fix|docs|style|refactor|perf|test|chore|ci|build|revert)(\(.+\))?(!)?: .{1,100}$'

if ! echo "$COMMIT_MSG" | grep -qE "$PATTERN"; then
    echo "ERROR: Commit message does not follow Conventional Commits format."
    echo "Expected: type(scope): description"
    echo "Example:  feat(auth): add OAuth2 login"
    echo "Types: feat fix docs style refactor perf test chore ci build revert"
    exit 1
fi
```

### pre-push (run tests before push)

```bash
#!/usr/bin/env bash
set -e

echo "Running tests before push..."
./vendor/bin/phpunit --testsuite=unit
echo "Tests passed. Pushing..."
```

---

## Conventional Commits Reference

```
<type>(<scope>): <short summary>

[optional body]

[optional footer(s)]
```

| Type | Use |
|------|-----|
| `feat` | New feature (triggers MINOR in semver) |
| `fix` | Bug fix (triggers PATCH) |
| `docs` | Documentation only |
| `style` | Formatting, whitespace (no logic change) |
| `refactor` | Code change without feat or fix |
| `perf` | Performance improvement |
| `test` | Adding or fixing tests |
| `chore` | Build process, deps, tooling |
| `ci` | CI/CD configuration changes |
| `build` | Build system changes |
| `revert` | Reverts a previous commit |

Breaking change: add `!` after type or `BREAKING CHANGE:` footer → triggers MAJOR.

```
feat(api)!: remove deprecated /v1 endpoints

BREAKING CHANGE: All /v1 endpoints removed. Migrate to /v2.
```

---

## Sparse Checkout (large monorepos)

```bash
# Enable sparse checkout
git clone --filter=blob:none --sparse https://github.com/org/monorepo.git
cd monorepo
git sparse-checkout init --cone

# Checkout only specific directories
git sparse-checkout set apps/api libs/shared

# Add more directories
git sparse-checkout add apps/web

# List active patterns
git sparse-checkout list

# Disable sparse checkout
git sparse-checkout disable
```

---

## Git LFS (Large File Storage)

```bash
# Install LFS (once per machine)
git lfs install

# Track file types
git lfs track "*.psd"
git lfs track "*.mp4"
git lfs track "*.zip"

# Commit the .gitattributes file
git add .gitattributes
git commit -m "chore: configure Git LFS tracking"

# List tracked patterns
git lfs track

# See LFS files
git lfs ls-files

# Migrate existing files to LFS
git lfs migrate import --include="*.psd" --everything
```

---

## Repository Maintenance

```bash
# Run full maintenance (gc, repack, prune)
git maintenance run --auto

# Aggressive garbage collection
git gc --aggressive --prune=now

# Count objects
git count-objects -vH

# Verify repository integrity
git fsck --full

# Pack loose objects
git repack -Ad

# Find large blobs in history
git rev-list --objects --all \
    | git cat-file --batch-check='%(objecttype) %(objectname) %(objectsize) %(rest)' \
    | awk '/^blob/ { print $3, $4 }' \
    | sort -rn \
    | head -20 \
    | numfmt --to=iec --field=1

# Clean untracked files (dry run first)
git clean -nxfd

# Clean untracked files (actual)
git clean -xfd
```

---

## Configuration

```bash
# Global identity
git config --global user.name "Your Name"
git config --global user.email "you@example.com"

# Default branch name
git config --global init.defaultBranch main

# Pull strategy (rebase by default)
git config --global pull.rebase true

# Auto-stash during rebase
git config --global rebase.autoStash true

# Autosquash fixup commits
git config --global rebase.autoSquash true

# Push only current branch
git config --global push.default current

# Color output
git config --global color.ui auto

# Better diff algorithm
git config --global diff.algorithm histogram

# Shared hooks directory
git config core.hooksPath .githooks

# Useful aliases
git config --global alias.st   "status -sb"
git config --global alias.lg   "log --oneline --graph --decorate --all"
git config --global alias.undo "reset --soft HEAD~1"
git config --global alias.wip  "commit -am 'WIP'"
git config --global alias.unwip "reset HEAD~1"
git config --global alias.aliases "config --get-regexp alias"
git config --global alias.recent "branch --sort=-committerdate --format='%(refname:short) — %(committerdate:relative)'"

# Show all config
git config --list --show-origin
```

---

## Emergency Procedures

### Accidentally pushed to main

```bash
# Revert the bad commits (safe — creates new commits)
git revert <bad-sha>..<HEAD>
git push origin main

# If you must hard reset (coordinate with team first)
git reset --hard <last-good-sha>
git push --force-with-lease origin main
```

### Lost commits after reset --hard

```bash
git reflog                          # find the sha
git checkout -b recovery <sha>      # recover to new branch
```

### Committed secrets / credentials

```bash
# Remove file from history immediately
git filter-repo --path secrets.env --invert-paths --force

# Force push all branches
git push origin --force --all
git push origin --force --tags

# Rotate the credentials — always, regardless of cleanup
```

### Corrupted repository

```bash
git fsck --full                     # find corrupted objects
git stash                           # save work if possible
cd ..
git clone --mirror origin-url repo-backup  # fresh clone from remote
```

### Merge went wrong

```bash
git merge --abort                   # if merge is in progress
git reset --hard ORIG_HEAD          # after a bad merge is committed (local only)
git revert -m 1 <merge-sha>        # safe revert for pushed merges
```

---

## Workflow

1. **Inspect first** — `git status -sb` and `git log --oneline -10` before every operation.
2. **Name branches well** — `type/issue-id-short-description` (e.g. `fix/1234-login-crash`).
3. **Commit atomically** — one logical change per commit; use `git add -p` to split mixed changes.
4. **Pull with rebase** — `git pull --rebase` to avoid noisy merge commits.
5. **Force-push safely** — always `--force-with-lease`, never `--force` on shared branches.
6. **Tag every release** — annotated tags with semver; push tags explicitly.
7. **Prune regularly** — `git fetch --prune` and delete merged branches.
8. **Use reflog** — before panicking about lost work, check `git reflog`.
