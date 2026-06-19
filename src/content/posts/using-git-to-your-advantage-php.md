---
title: "Using Git to Your Advantage - Advanced Git for PHP Developers"
description: "Master advanced Git workflows including interactive rebase, bisect, blame, stash, reflog, hooks, worktrees, and team workflows for PHP projects."
pubDate: "2022-08-16 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Git", "PHP", "Version Control", "Workflow", "Productivity"]
selected: false
---

Version control with Git is a skill every developer needs. But most developers only scratch the surface: commit, push, pull, merge. Git offers a wealth of advanced features that transform how you work. Interactive rebase, worktrees, the reflog, and hooks turn Git from a backup tool into a productivity powerhouse.

This article builds on the fundamentals and dives into the tools that separate competent Git users from power users.

## Worktrees: Multiple Branches, One Repository

Git stash works for small context switches, but it falls apart with untracked files, complex changes, or large sets of modifications. Worktrees solve this by creating linked, separate copies of your repository. Each worktree lives in its own folder but shares the same repository metadata.

Here's the scenario: you're knee-deep in a new feature when a critical bug surfaces. Instead of stashing partial work or committing broken code, create a worktree:

```bash
git worktree add -b bugfix/file-read-bug \
    ~/Projects/fix-file-read main
```

This creates a new branch `bugfix/file-read-bug` checked out in `~/Projects/fix-file-read`, based on `main`. Your original branch stays untouched. Work on the bug fix in the new folder, commit, push, and submit a pull request. Your feature branch waits patiently.

List active worktrees:

```bash
$ git worktree list
/home/ctankersley/Projects/test-project  0c62e8c [newfeature]
/home/ctankersley/Projects/fix-file-read 0c62e8c [bugfix/fix-file-read]
```

Remove a finished worktree:

```bash
git worktree remove ~/Projects/fix-file-read
```

If you delete a worktree folder manually, run `git worktree prune` to clean up stale references.

Worktrees shine when you maintain multiple versions simultaneously—a mainline branch, a beta version, and a rewrite. Each stays isolated, yet they share the same object database.

## Rebasing: Rewriting History With Purpose

Rebasing rewrites history by changing the order or content of commits. There are two philosophical camps:

**The historical record camp** sees the Git timeline as an accurate representation of what happened. Every typo fix, every intermediate commit, every merge matters. Rebasing should be minimized.

**The story camp** sees the timeline as a narrative. The messy intermediate steps aren't important—what matters is the clean, atomic changeset that tells the story of *what* changed and *why*.

This article takes the story approach. Your pull requests should be coherent, not a diary of every compile error you fixed along the way.

### Cleaner Pulls

When you `git pull` on a branch like `main`, Git fetches remote changes and merges them. If you have local commits, this creates a merge commit:

```
Merged branch 'main' of origin into main
```

Avoid this with a rebasing pull:

```bash
git pull --rebase origin main
```

This replays your local commits on top of the remote changes, producing a linear history with no unnecessary merge commits.

### Cleaner Updates From the Base Branch

When your feature branch drifts behind `main`, you need to bring in upstream changes. The merge approach creates pollution:

```
63ac1c56 Now       - Merged branch main
244426b0 2 hours   - Merged branch feature/time-feature
c23d5765 10 hours  - Updated dependencies
a7ec8a44 3 days    - Added Time class
```

Rebase instead:

```bash
git rebase main
```

Git pulls your commits off, fast-forwards to the latest `main`, and replays your changes on top. The history becomes linear and clean:

```
cbc1eff2 now    - Updated dependencies
244426b0 2 hours - Merged branch feature/time-feature
```

Note that your commit hash changes. Rebasing creates new commits with new hashes because the parent and file tree change.

### Cleaning Up History With Interactive Rebase

Before pushing a feature branch, clean up the history. Consider this log:

```
2202910e 2 days ago - Fixed phpstan errors
7eaec88b 2 days ago - Updated header to new logo
475c4c0a 3 days ago - Fixed unit tests
3c76e322 3 days ago - Typo fix
f523a40d 3 days ago - Fixed bug in Service Manager
cbc1eff2 4 days ago - Updated dependencies
```

You don't need a separate commit for a typo fix. Combine it with the parent commit using interactive rebase:

```bash
git rebase -i cbc1eff2
```

Git opens an editor with the commit list:

```
pick 2202910e Fixed phpstan errors
pick 7eaec88b Updated header to new logo
pick 475c4c0a Fixed unit tests
pick 3c76e322 Typo fix
pick f523a40d Fixed bug in Service Manager
pick b2b99fb Updated dependencies
```

Change `pick 3c76e322` to `fixup 3c76e322`. This discards the commit message and merges the changes into the previous commit. Save and close. The result:

```
0597e76e A moment ago - Fixed phpstan errors
5e17ff34 A moment ago - Updated header to new logo
58cc8c75 A moment ago - Fixed unit tests
495c9b12 A moment ago - Fixed bug in Service Manager
cbc1eff2 4 days ago - Updated dependencies
```

The typo fix is gone. All downstream commits have new hashes.

### The Golden Rule of Rebasing

Never rebase a branch that someone else has committed to. Rebasing creates new commits with new parents, which diverges from the remote. Pushing requires `--force`, which overwrites the remote history and destroys your teammates' work.

If you're the only person on a branch, rebase freely. If others are working there too, merge instead.

## Git Bisect: Find the Bug Commit

When a bug appears and you don't know which commit introduced it, `git bisect` performs a binary search through your history:

```bash
git bisect start
git bisect bad          # Current commit is broken
git bisect good v1.2    # Tag v1.2 was working
```

Git checks out the midpoint commit. Test it. Mark it:

```bash
git bisect good     # This commit works
# or
git bisect bad      # This commit is broken
```

Repeat until Git identifies the first bad commit. In 10 steps, you can search through 1000 commits. Automate it with a script:

```bash
git bisect run php vendor/bin/phpunit
```

When the failing test passes, Git marks the commit good. When it fails, Git marks it bad. The first failing commit is the culprit.

## Git Blame: Trace Every Line

`git blame` annotates every line of a file with the commit that last modified it and the author:

```bash
git blame src/Controllers/GameController.php
```

The output shows commit hash, author, date, and line content. When you see a confusing line, blame tells you who last touched it and when. This isn't about assigning fault—it's about understanding context. The commit message often explains *why* that line exists.

## Git Stash: Temporary Shelving

Stash saves uncommitted changes and reverts your working directory to the last commit:

```bash
git stash                    # Stash tracked files
git stash --include-untracked  # Include new files
```

List stashes:

```bash
git stash list
stash@{0}: WIP on main: abc1234 Updated dependencies
stash@{1}: WIP on feature: def5678 Added time class
```

Apply a stash:

```bash
git stash pop stash@{1}  # Apply and remove from stash list
git stash apply stash@{1} # Apply but keep in stash list
```

Stash is ideal for quick context switches. Worktrees are better for long-running parallel work.

## Reflog: Recovery Tool

The reflog tracks every action that changes HEAD, including lost commits:

```bash
git reflog
abc1234 HEAD@{0}: commit: Updated dependencies
def5678 HEAD@{1}: commit: Fixed bug
7890abc HEAD@{2}: rebase -i (finish): returning to refs/heads/main
```

Accidentally reset too far? Lost commits from a botched rebase? Find the commit in the reflog and recover it:

```bash
git checkout abc1234  # Detached HEAD at the lost commit
git branch recover-branch    # Create a branch pointing here
```

The reflog is local and expires after 90 days by default. Push doesn't share it—it's your safety net.

## Git Hooks: Automate Your Workflow

Hooks are scripts that run at specific points in the Git lifecycle. They live in `.git/hooks/` and are local to each clone. Common hooks:

| Hook | Trigger |
|------|---------|
| `pre-commit` | Before commit message is saved |
| `pre-push` | Before push executes |
| `post-merge` | After a merge completes |
| `pre-receive` | On the server before accepting push |

Here's a `pre-commit` hook that runs PHP lint on staged files:

```bash
#!/bin/sh
for file in $(git diff --cached --name-only --diff-filter=ACMT | grep '\.php$'); do
    php -l "$file" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "PHP lint failed on $file"
        exit 1
    fi
done
```

Install with:

```bash
chmod +x .git/hooks/pre-commit
```

Share hooks across your team using a `hooks/` directory in your repository and a setup script, or use a tool like CaptainHook.

## Git Flow vs. Trunk-Based Development

Two common workflows suit different team structures.

### Git Flow

Git Flow defines a strict branching model:

- `main` — production-ready code
- `develop` — integration branch for features
- `feature/*` — individual feature work
- `release/*` — preparation for a release
- `hotfix/*` — urgent production fixes

Advantages: clear separation, works well for release-driven projects with versioned deployments.

Disadvantages: overhead from maintain branches, complex for continuous deployment.

### Trunk-Based Development

All developers work on short-lived feature branches off `main` and merge frequently (multiple times per day). Feature flags hide incomplete work in production.

Advantages: simple, minimal merge conflicts, enables continuous deployment.

Disadvantages: requires discipline with feature flags, harder for large features spanning weeks.

Choose based on your deployment cadence and team size. Most modern CI/CD pipelines favor trunk-based development.

## Conclusion

Git's advanced features transform it from a simple version control tool into a productivity multiplier. Worktrees handle parallel work without stash gymnastics. Interactive rebase produces clean, reviewable pull requests. Bisect finds bugs efficiently. Blame provides context. The reflog saves you from mistakes. Hooks enforce quality automatically.

The Git Book's tools section goes deeper into each topic. Experiment with these commands in a safe repository. Once you're comfortable, integrate them into your daily workflow. The upfront effort pays back in hours saved every week.
