---
title: "What is Git Doing? - Understanding Git Internals"
seoTitle: "What is Git Doing? - Understanding Git Internals for PHP Developers"
description: "Understand Git internals from a PHP developer's perspective. Learn how commits, branches, and merges work under the hood."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-07-14 21:00:00"
tags: ["Git", "Git Internals", "Version Control", "PHP", "DevOps"]
---

Every developer learns Git eventually. You memorize the incantations: `git add`, `git commit`, `git push`. When something breaks, you Google the error, copy-paste a fix from Stack Overflow, and move on. But have you ever stopped to ask: what is Git actually doing?

Understanding Git's internals transforms you from someone who follows recipes into someone who understands why those recipes work. It makes merge conflicts less scary, rebasing less mysterious, and debugging history issues actually approachable.

## A Brief History of Version Control

Before jumping into Git, it helps to understand what came before.

### First Generation: RCS

The Revision Control System (RCS) managed versioning for individual files. It used a **reverse-delta** system: store the current version of a file plus the differences needed to reconstruct each previous version. Reverting a file meant applying deltas in reverse. This saved space but assumed most reverts would only go back a few revisions.

### Second Generation: CVS and SVN

The Concurrent Versions System (CVS) wrapped RCS with project-level versioning and client-server architecture. Multiple users could change the same file, but CVS suffered from non-atomic commits—if your network dropped mid-commit, you'd have a half-updated repository.

Subversion (SVN) fixed this with atomic commits. It stored the original file plus subsequent deltas, rebuilding the current version on checkout. Every 1023 revisions, it stored a full copy to balance checkout performance. SVN dominated the early 2000s, but its centralized model meant you needed network access for every operation.

### Third Generation: Git

Git brought decentralization. Every developer has a full copy of the repository. There's no single canonical source—just forks that exchange changes. This model, paired with GitHub's collaboration platform, has made Git the dominant version control system.

## Git's Object Model

Unlike earlier systems, Git doesn't use deltas for file history. It stores **full snapshots** of files. These snapshots are called **blobs** (binary large objects), and they're referenced only by a SHA-1 hash of their content.

Wait—doesn't `git diff` show changes as diffs? Yes, but those diffs are **generated on demand** by comparing two snapshots. Git doesn't store diffs; it stores complete file versions and computes differences when asked.

### The Four Object Types

Git has four fundamental object types:

**Blobs** store file contents. A blob has no metadata—no filename, no permissions, nothing but the raw bytes. The hash identifies the content itself. If two files have identical content, they share the same blob.

**Trees** represent directory structure. A tree maps filenames to blobs (for files) or other trees (for subdirectories). Each tree entry includes the file mode, type, hash, and filename.

**Commits** are snapshots of the entire repository at a point in time. A commit stores:
- A pointer to a tree (the root directory snapshot)
- Parent commit hashes
- Author and committer metadata
- A timestamp
- A commit message

**Tags** are human-readable labels pointing to a specific commit. Annotated tags store additional metadata like a tag message and the tagger's identity.

### How Git Stores History

Here's the counterintuitive part: Git's history is stored **backward**. When most people visualize commits, they draw them in chronological order—commit 1, then commit 2, then commit 3. But internally, each commit only knows its parent, not its children.

```
commit 1 <- commit 2 <- commit 3 <- HEAD
```

When you make commit 3, Git:
1. Creates blobs for every changed file
2. Creates a tree representing the current directory structure
3. Creates a commit pointing to that tree
4. Sets commit 3's parent to commit 2
5. Moves HEAD to commit 3

When you run `git log`, Git starts at HEAD and follows parent pointers backward until it finds a commit with no parent (the initial commit).

## Understanding HEAD

HEAD is a special pointer that tells Git "this is where we are right now." Usually, HEAD points to a branch (like `main`), which in turn points to a commit. When you make a new commit, the branch pointer advances, and HEAD follows because it points to that branch.

In a detached HEAD state, HEAD points directly to a commit instead of a branch. This happens when you check out a specific commit hash or tag. Any commits you make won't be associated with a branch, making them vulnerable to garbage collection.

## Branches Are Just Pointers

This parent-based architecture makes branching trivial. A branch is literally just a pointer to a commit. Creating a new branch creates a new pointer:

```bash
git branch feature-x
```

That's it. A file with a commit hash in `.git/refs/heads/feature-x`. Git knows which commit belongs to which branch by tracking which branch pointer points where.

### Merging

When you merge, two things can happen:

**Fast-forward merge**: Git determines no reconciliation is needed. It simply moves the target branch pointer forward to match the source branch. No new commit is created.

**Merge commit**: When both branches have diverged (changes on both sides need reconciling), Git creates a special commit with **two parents**. This merge commit represents the combination of both lines of development.

```
          A---B---C feature
         /         \
    D---E---F---G---H main (merge commit)
```

### Rebasing

Rebasing rewrites history by replaying commits on top of another base. Instead of a merge commit, each commit from the feature branch is re-applied sequentially onto main:

```bash
git checkout feature
git rebase main
```

```
          A'--B'--C' feature
         /
    D---E---F---G main
```

The original commits A, B, C still exist (until garbage collection), but the feature branch now points to new commits A', B', C' with different hashes. This is why you never rebase shared branches—you're rewriting history that others may have based work on.

## Poking Around .git

Let's demystify the `.git` directory. Run `ls -la .git` in any repository:

```
HEAD
config
description
hooks/
index
info/
objects/
refs/
```

- **HEAD**: A file containing `ref: refs/heads/main` (or whatever branch you're on)
- **config**: Repository-specific configuration
- **objects/**: The object database. Blobs, trees, commits, and tags stored by hash
- **refs/heads/**: Branch pointers (files containing commit hashes)
- **refs/tags/**: Tag pointers
- **index**: The staging area (a binary file tracking what will be committed)

Run `git cat-file -p HEAD` to see the commit object that HEAD points to. Run `git cat-file -p <tree-hash>` to see the tree. You're traversing Git's internal data structure manually.

## Git's Practical Magic

Understanding internals makes everyday Git operations clearer:

**Why does `git checkout -b` work instantly?** Because creating a branch is just writing a 40-character hash to a file.

**Why does `git rebase -i` let you reorder/squash commits?** Because you're just creating new commit objects with different parents. The old commits remain in the object database until garbage collection.

**Why does `git reset --hard` feel dangerous?** Because it moves the branch pointer and updates the working tree. If you've done this and lost work, `git reflog` shows you where HEAD used to be—the commits still exist in the object database for 30 days.

**Why can't Git handle large files well?** Because every version of a large file is stored as a full blob. Git LFS was created to replace blobs with pointers and store large files elsewhere.

## Git in the PHP Development Workflow

For PHP developers, understanding Git internals helps in practical ways:

**Deployments**: Knowing that `git checkout` switches the entire working tree explains why zero-downtime deployments often use symlinks to swap between releases rather than git operations on live servers.

**CI/CD pipelines**: When your CI tool runs `git fetch --depth=1` (shallow clone), it gets only the latest commit without full history. Understanding this explains why some git operations fail in CI—there's no parent chain to traverse.

**Debugging production issues**: `git bisect` does a binary search through history to find where a bug was introduced. Understanding the commit graph helps you appreciate why this is so efficient.

**Squashing before PRs**: Squashing creates a new commit that incorporates all changes from a feature branch. The original commits still exist in the object database but become unreachable. GitHub's "Squash and merge" button automates this.

## The Index: Git's Staging Area

The index (or staging area) is one of Git's most misunderstood features. It's a binary file at `.git/index` that tracks which files will be included in the next commit. Think of it as a pre-flight checklist for your commit.

When you run `git add file.php`, Git:

1. Creates a blob for the current version of `file.php`
2. Adds an entry to the index mapping `file.php` to that blob hash
3. The file is now "staged"

When you run `git commit`, Git:

1. Reads the index to know which blobs to include
2. Builds a tree from the index entries
3. Creates a commit pointing to that tree with the current HEAD as parent
4. Clears the index for the next commit

The index is why you can selectively stage changes. `git add -p` lets you stage individual hunks within a file, creating multiple index entries for different parts of the same working tree file. This is impossible in systems without a staging area.

## Cherry-Picking and Reflog

**Cherry-pick** creates a new commit that applies the changes from an existing commit onto the current branch. Internally, Git computes the diff between the cherry-picked commit and its parent, then applies that diff as a new commit on the current branch:

```bash
git cherry-pick abc1234
```

This creates a new commit with different parent, different hash, but the same author, message, and diff as `abc1234`. The original commit remains unchanged.

**Reflog** (reference log) is Git's safety net. Every time HEAD moves—commit, checkout, reset, rebase, merge—Git writes an entry to `.git/logs/HEAD`. The reflog shows where you've been, not just where you are:

```bash
git reflog
# abc1234 HEAD@{0}: commit: Fix login bug
# def5678 HEAD@{1}: commit: Add tests for login
# ghi9012 HEAD@{2}: reset: moving to HEAD~1
```

If you accidentally `git reset --hard` and lose work, the reflog tells you the commit hash to recover. Reflog entries expire after 90 days (30 for unreachable commits), giving you a generous window for recovery.

## The Packfile: Git's Compression Strategy

Storing every version of every file as a full blob would be prohibitively expensive for large repositories. Git solves this with **packfiles**.

When you run `git gc` (garbage collection), or when Git decides it's time, it packs loose objects into a packfile. A packfile stores:

- The full content of each object once
- Deltas (differences) for objects that are similar to each other

Packfiles use heuristics to find similar objects and store only the delta between them. This gives Git the best of both worlds: fast snapshot-based operations during development and space-efficient storage after packing.

```bash
# Manually trigger garbage collection
git gc

# Aggressive optimization (takes longer but compresses better)
git gc --aggressive

# Count objects in the repository
git count-objects -v
```

A well-packed Git repository is surprisingly small. The Linux kernel repository, with over a million commits, weighs in at under 2GB when packed. Without packing, it would be orders of magnitude larger.

## Submodules and Subtrees

When your PHP project depends on libraries that aren't easily managed through Composer, Git provides two mechanisms:

**Submodules** embed one repository inside another as a reference to a specific commit:

```bash
git submodule add https://github.com/example/library packages/library
```

The parent repository stores a pointer (commit hash) to the submodule. Developers must run `git submodule update --init` after cloning. Submodules are useful when you need to pin an external dependency to an exact version and track its history.

**Subtrees** merge an external repository's history into your repository at a specific path. Unlike submodules, subtrees don't create external references—the code becomes part of your repository:

```bash
git subtree add --prefix=packages/library https://github.com/example/library main --squash
```

Subtrees are simpler for teams that don't want to teach everyone submodule commands. The trade-off is that your repository grows to include the external project's entire history.

## Common Pitfalls and Their Internal Explanations

### "I accidentally committed to the wrong branch"

Since branches are just pointers, fix this by moving the branch pointer:

```bash
# Move main back one commit
git branch -f main main~1

# Create a new branch at the lost commit
git branch fix/wrong-branch abc1234
```

No data is lost. The commit still exists in the object database and is discoverable via reflog.

### "My rebase went wrong"

Rebasing creates new commits. The old commits are still in the object database:

```bash
# Go back to where you were before rebase
git reset --hard ORIG_HEAD

# ORIG_HEAD is set by Git before dangerous operations
```

This works because Git sets `ORIG_HEAD` before rebase, merge, and reset operations. Your original branch state is always one `ORIG_HEAD` away.

### "Git says 'detached HEAD'"

In detached HEAD state, HEAD points directly to a commit instead of a branch. New commits update HEAD but no branch pointer:

```bash
# Create a branch at the current position to save work
git branch new-branch-name

# Now switch back
git checkout new-branch-name
```

Without a branch, Git's garbage collection will eventually clean up commits not reachable from any branch or tag. Always create a branch when you want to keep work done in detached HEAD state.

## Git and Composer Integration

For PHP developers, Git knowledge intersects with Composer in several important ways:

**Composer reads `.gitignore` patterns**. When Composer detects it's running inside a Git repository, it respects `.gitignore` patterns for file operations. This affects commands like `composer install` and `composer create-project`.

**The `composer.lock` file must be committed**. The lock file pins exact dependency versions. Without it in version control, different developers and deployment environments will get different dependency versions, leading to "works on my machine" bugs.

**The vendor directory must NOT be committed**. `.gitignore` should contain `/vendor/`. The `vendor/` directory is rebuildable from `composer.lock` and bloats the repository unnecessarily.

**Composer's `--prefer-dist` flag**. During `composer install`, Composer downloads distribution packages (zipped archives) by default, which is faster than cloning Git repositories. Use `--prefer-source` when you need to modify a dependency.

**Git tags and Composer versions**. Composer reads Git tags to determine available versions. A tag like `v1.0.0` tells Composer that version 1.0.0 is available. Semantic versioning in Git tags directly powers Composer's version resolution.

## Conclusion

Git's object model is elegant in its simplicity: blobs for content, trees for structure, commits for history, and tags for labels. Everything else—branches, merges, rebases—is built on this foundation. Branches are just pointers. History is a parent chain stored backward. Snapshots are stored whole, and diffs are computed on demand.

Next time you run `git commit`, picture what's happening under the hood. Git is taking a snapshot of every changed file, building a tree of your project structure, wrapping it in a commit with a pointer to its parent, and moving HEAD forward. That's all there is to it.
