---
title: "Faster Network File Sync with `rclone` (vs `rsync`)"
description: "The PHP Foundation just dropped a fat security audit on `php-src` — that’s the engine behind PHP itself — and yeah, it wasn’t pretty. Some high-sev vulnerabilities were found lurking in the dark corners"
pubDate: "2025-05-01 21:00:00"
category: "tool"
banner: "/logo.svg"
tags: ["Tips", "Programming","Technical", "Linux"]
selected: false
---

For the past couple years, I’ve lugged around my “working set” — a hefty, ever-growing collection of video projects and random chaos — on an external Thunderbolt NVMe SSD. It’s my daily shuttle. And yeah, it’s fast. *On paper.*

But syncing those files? Painfully slow. I'm talking *slower than it should be*. 😤

A typical day sees me drop 500–1000 new files, with a handful weighing in at 1–10 GB each. Multiply that by a few project folders and... you get the idea.

My gear’s no slouch:

* 🔥 External Thunderbolt NVMe SSD: Capable of 5+ GB/sec.
* 🌐 10 GbE network connection: Can hit 1 GB/sec.
* 🚀 Upgraded to Thunderbolt 5: Nice, but didn’t solve the sync bottleneck.

So the weak link? The **sync method**. I had been using:

```bash
rsync -au --progress --stats /drive/sda/* /Volumes/Shuttle/Video_Projects
```

Where `/drive/` is a fast NVMe-backed NAS share from my Arm-based NAS (fittingly named “Mercury”), and “Shuttle” is the SSD I carry around.

And while `rsync` is rock solid — it *gets the job done* — it doesn’t seem to care much about using all that juicy bandwidth and IOPS I’ve got lying around.

---

### ⚡ Meet `rclone`: Your Sync Workhorse on Steroids

So I swapped `rsync` out with [`rclone`](https://rclone.org/), which is usually thought of for cloud syncs... but turns out it *slaps* for local network work too.

Here’s the basic `rclone` command I used:

```bash
rclone copy /drive/sda/ Shuttle:Video_Projects --progress --transfers=32 --checkers=64 --fast-list
```

Or with a local path target:

```bash
rclone copy /drive/sda/ /Volumes/Shuttle/Video_Projects --progress --transfers=32 --checkers=64 --fast-list
```

Tweakable. Efficient. And **way faster**.

---

### 📈 Benchmarks (Unofficial but Real)

I ran a few real-world tests across the same dataset, doing cold syncs (no files cached). The results?

| Tool     | Avg. Sync Time | Peak Throughput  |
| -------- | -------------- | ---------------- |
| `rsync`  | \~16 minutes   | \~200–250 MB/sec |
| `rclone` | \~4 minutes    | \~800–900 MB/sec |

We're talking about **4x faster** syncs — basically using up every bit of that 10 Gbps link without breaking a sweat. 🙌

---

### 🔍 Why Is It Faster?

1. **Multithreaded Transfers**: `rclone` doesn’t just crawl file by file — it throws multiple transfers at the problem.
2. **Aggressive File Checking**: With `--checkers` and `--fast-list`, it ramps up directory scanning.
3. **Better Parallelism**: It scales better across high-I/O file systems and fast networks.
4. **Smarter Caching**: If you do repeated syncs, `rclone` benefits more from file system caching (especially on macOS).

---

### 💡 Takeaways

* ✅ If you're syncing lots of small files or giant folders, **use `rclone`** — not just for the cloud.
* ✅ Tweak `--transfers` and `--checkers` depending on your network/CPU.
* ✅ Keep using `rsync` for surgical precision or POSIX metadata needs, but consider `rclone` when raw speed matters.

---