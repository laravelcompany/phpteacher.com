---
title: "Backups for Developers - 3-2-1 Strategy, Tools, and Best Practices"
description: "Learn the 3-2-1 backup strategy, full vs incremental vs differential backups, and how to build a disaster recovery plan that actually works. Essential guide for every developer."
pubDate: "2022-03-10 21:00:00"
category: "tool"
banner: "/logo.svg"
tags: ["Backups", "DevOps", "Disaster Recovery", "Data Loss", "3-2-1 Backup", "Developer Tools", "IT"]
selected: false
---

# Backups for Developers: 3-2-1 Strategy, Tools, and Best Practices

Backups are one of those things we developers don't like to think about until there's a problem, and then it's too late. Anyone who has ever accidentally deleted something only to find out the only good backup is weeks or months old can tell you how important it is to always have backups as part of your disaster recovery plan. You can't let them slip, because not having a functioning backup can cost your company untold hours and thousands of dollars recreating data. The worst part is apologizing to your users that their data is gone.

In this guide, you'll learn what a backup actually is, why you need one, the four categories of data loss that can destroy your business, the legendary 3-2-1 backup strategy, how to choose between full, incremental, and differential backups, and the modern tools — including PHP-specific options — that make all of this easier than ever.

## World Backup Day

March 31 is World Backup Day — "the day to prevent data loss." The idea is to bring awareness to the importance of backups and what can happen when you don't have a good copy of your important data. It's primarily geared toward consumers, but backups are critical for anyone in Development, DevOps, Operations, and IT.

If you've been given the keys to a company's critical data, you don't want to be the one telling the CEO there's no way to restore data after a ransomware attack or when your largest client's data disappears. Otherwise, you'd better be prepared to switch careers.

Every day is World Backup Day for anyone supporting an application. Keep it in the back — or front — of your mind at all times.

## What Is a Backup?

A backup is a copy of data stored elsewhere so it can be used in the event of data loss to restore the original data. Backups are a form of disaster recovery, and for several types of data backups, they can completely recreate the original data.

However, some backups may not be able to fully restore everything. For example, you might not have a backup of data that lives only in a caching layer where data is never written to disk. You need to plan for these gaps and know how to act on them.

## Why You Need Backups

After working with other people's data for over two decades, I've learned the hard way that it's not a question of *if* you'll need your backups, but *when*. There are four major categories of data loss you need to be prepared for.

### Human Error

Human error data loss happens through accidents and unknowingly modifying or deleting data. This is the category you'll deal with most often as a developer. How many times has someone deleted Client A's data when they meant to delete Client B's?

I have vivid memories of an incident where a support rep deleted our largest client's data. We had a very angry client calling as we invented a way to restore just their data on the fly. The CEO wasn't happy with "we're not sure how to get their data back," and it's not something I ever want to repeat.

### File Corruption

File corruption happens when a software bug writes bad data or a virus infection corrupts files. This is the type of data loss that keeps me up at night. Think about the very public ransomware attacks that have taken down companies, school districts, and even gas pipelines. Ransomware encrypts your files and demands payment for the decryption key. A clean, tested backup is your only defense that doesn't involve paying criminals.

### Hardware Failure

Hardware failure is caused by drives, RAID controllers, and occasionally CPUs failing. In a world of virtual data centers, this is becoming less of a worry for server infrastructure, but it's still a real issue for company laptops and on-premise equipment. Think about all the documents sitting on your desktop that you can't recreate. Maybe that file of passwords you don't store anywhere else for "security purposes."

### Physical Site Destruction

The last category is physical destruction. Data can be lost to floods, fires, earthquakes, and theft. For some real nightmare-inducing results, search for "Flooded Server Room" images. Server rooms are catastrophically expensive to rebuild, and without offsite backups, the data in them is gone forever.

Thankfully, you can reduce these risks dramatically with a sensible backup strategy.

## Test Your Backups Regularly

The single most important thing about backups is that you test them. Test that they can be read. Test that you can install the software to perform the restores. Test that the media is still readable. It does you no good to have a backup if, when you need it, you can't restore the data — or restoring takes too long to be useful.

Best practice is to test your backup at least once a year and then whenever there's a significant change to your technology infrastructure. Do a full restore test annually and single-file restores at least once a month. November and December tend to be quieter months for many businesses, making them a great window for the yearly full test.

Test your disaster recovery plan during the yearly backup test. Time yourselves to see how long it takes to go from nothing to a fully running test environment. Iron out any issues you discover during the process. Randomly assign someone on the team to perform the full restore so anyone who might have to do it for real gets experience.

If you take only one thing from this guide, let it be this: go test your backups right now.

## The 3-2-1 Backup Rule

One of the most effective concepts for protecting data is the 3-2-1 Backup Strategy. The idea is simple: have **3 copies** of your data on **2 different local devices** and **1 offsite backup**.

Three copies means your working copy plus two backups. Two different devices means exactly that — not the same RAID array or the same physical server. One offsite backup means a copy stored in a completely different physical location, so a fire or flood at your primary site doesn't destroy everything.

The 3-2-1 rule lets you restore quickly from local backups in cases of human error, file corruption, or hardware failure, and still recover if your physical site is destroyed.

Here's a real-world example. I'm writing this on a MacBook (local copy 1). Backup software copies my changes to a NAS in the basement (local copy 2). That NAS automatically uploads to a cloud service every night (offsite backup 1). If I spill coffee on my laptop, accidentally delete a file, or have both my laptop and NAS stolen, the hardware is easily replaced and my data is safe in the cloud.

### Git as a 3-2-1 Backup

As developers using a distributed version control system like Git, you get a 3-2-1 Backup almost for free. Your local working directory is copy 1. Your local Git repository is copy 2. And if you're pushing to GitHub, GitLab, or Bitbucket, that's your offsite backup. It's one of the best features of distributed version control — your entire project history is replicated across multiple machines by default.

### 3-2-1 for Cloud and Virtual Servers

If your servers live in the cloud, you still need to follow the 3-2-1 rule. For your offsite backup, use a *different cloud provider* than your primary infrastructure. Cloud storage providers are everywhere — AWS S3, Backblaze B2, Google Cloud Storage, Azure Blob Storage — so you have plenty of options.

For the second local copy, spin up a dedicated backup server in your data center or attach an extra virtual drive on a separate data store. If you can put that backup server in a different availability zone or data center, you get bonus points.

Talk to your hosting provider about their backup options. Some only offer full VM restores from a once-a-day backup. Others can restore individual files with hour-level granularity. Know what you're getting before you need it.

I once worked on a project using a "full service" hosting provider. They maintained five-nines of availability, kept hosts patched, and managed backups. Everything was perfect until the provider hit a router bug that took their entire data center offline for a full working day. The client lost money by the hour, and we couldn't spin up elsewhere because our data was locked into that provider's cloud. If we had set up an offsite backup with a different provider, we could have been back online in minutes.

## Modern Backup Tools

The original article recommended rsync and cloud storage. Here's what the modern backup landscape looks like.

### Restic

Restic is a fast, secure, open-source backup program written in Go. It supports encryption out of the box, deduplication, and multiple backends including local directories, SFTP, AWS S3, Backblaze B2, Google Cloud Storage, and Azure Blob Storage. A single command like `restic -r s3:s3.amazonaws.com/bucket backup /path/to/data` encrypts and uploads your data in seconds. Restic integrates with cron or systemd timers for automated nightly backups.

### BorgBackup

Borg is a deduplicating backup tool for Linux, macOS, and BSD. It's known for extremely efficient compression and deduplication — successive backups of similar data take almost no additional space. Borg supports encryption and remote backups over SSH. It's mature, battle-tested, and widely used in production environments.

### rsync

rsync is the old reliable. It's preinstalled on virtually every Linux and macOS system, supports incremental file transfer over SSH, and works perfectly for simple backup scripts. Combine rsync with a cron job and you have a basic but effective backup system in minutes.

### AWS Backup

If you're all-in on AWS, AWS Backup provides a centralized, fully managed backup service. It supports EC2 instances, RDS databases, EFS file systems, and S3 buckets with a single policy-based configuration. You define backup plans, retention rules, and lifecycle policies — AWS handles the rest.

### PHP and Laravel-Specific Backup Tools

For PHP developers, **spatie/laravel-backup** is the gold standard. Created by the Spatie team, this package lets you backup your entire Laravel application — database, files, and folders — to multiple destinations with a single Artisan command. It supports S3, SFTP, Dropbox, and local disk. You can schedule backups via Laravel's scheduler, monitor backup health, and get notified when something goes wrong. A typical setup looks like this:

```php
// config/backup.php
return [
    'backup' => [
        'source' => [
            'files' => [
                'include' => [base_path()],
                'exclude' => [base_path('vendor'), base_path('node_modules')],
            ],
            'databases' => ['mysql'],
        ],
        'destination' => [
            'disks' => ['s3', 'local'],
        ],
    ],
];
```

For non-Laravel PHP projects, **php-backup** by the PHPLeague provides a framework-agnostic backup solution. You can compose different backup and storage adapters to fit your exact workflow.

## Full, Incremental, or Differential Backups?

There are four main types of backups. Choosing the right combination depends on your data size, recovery time objectives, and budget.

### Full Backup

A full backup contains all of your data. Every file, every byte. The advantage is simplicity — restoring means restoring a single backup. The downside is size and speed. Full backups take the most storage space and the longest to complete. Running a full backup every day requires significant storage capacity and network bandwidth.

### Differential Backup

A differential backup contains all changes since the last full backup. Start with a full backup on a fixed schedule (say, Sunday night). Then every subsequent backup captures everything that changed since that full backup. To restore, you need only the last full backup plus the latest differential backup. The tradeoff is that differential backups grow larger over time as more changes accumulate.

### Incremental Backup

An incremental backup contains only the changes from the *previous* backup of any type — not just the last full backup. This makes each incremental backup small and fast. The downside: restoring requires the last full backup *plus every single incremental backup in sequence*. If you run incremental backups daily for two weeks, that's 15 separate files to restore. Lose one, and everything after it is unrecoverable.

### Continuous Data Protection (CDP)

CDP is the most recent addition to backup methods. Instead of performing backups on a schedule, CDP constantly captures every write operation as it happens. You can theoretically restore to any point in time. The overhead is significant — every write must be performed twice or three times — but for systems that cannot tolerate any data loss, CDP is the only option.

As a PHP developer, you can implement application-level CDP by writing data to multiple locations simultaneously. For example, when a user uploads a photo that can't be recreated, save it to two S3 buckets at one provider and one at another the moment the upload completes.

## Retention Time

Retention time defines how long you keep each backup. You could keep every version of every file forever, but storage costs add up fast. You need to define how long each backup is retained, and once that period expires, reuse or recycle the media.

The right retention period depends on several factors:

1. **Regulatory requirements** — If you're subject to HIPAA, GDPR, PCI-DSS, or SOC 2, specific retention minimums may apply.
2. **Budget** — You'll need to justify why you're spending what you're spending on storage.
3. **Business value** — Is a backup from a year ago actually useful?

A good rule of thumb is to keep three months of backups. Data loss tends to be discovered either immediately or after a very long time (when nobody can remember the file name). Beyond three months, the utility drops significantly for most organizations.

## Choosing a Backup Generation Strategy

Now that you understand the terminology, let's look at the practical options for generating backups day to day.

### Full Backups Every Day

The simplest approach: run a full backup every single day. Restoring is as easy as restoring the latest full backup. The cost in storage and network bandwidth can be significant, but it might not be as expensive as you think. Run the numbers for your dataset. You might find it's surprisingly affordable.

You can reduce costs with tiered retention — keep Sunday backups for three months but only keep Monday through Saturday backups for two weeks. You still have three months of coverage, just not for every day of that period.

### Full Plus Incremental or Differential

Run a full backup on a schedule (weekly or monthly) and incremental or differential backups in between. This reduces storage and transfer costs dramatically. The tradeoff is that full restores take longer because you need to restore at least two backup sets.

### Grandfather-Father-Son (GFS)

The Grandfather-Father-Son scheme is a structured rotation that balances cost, retention, and restore speed:

1. **Grandfather**: A full backup done once per month, stored separately — ideally offsite or on different media.
2. **Father**: A full backup done once per week, kept on-site with an offsite copy if possible.
3. **Son**: Incremental or differential backups run daily, stored alongside the father.

GFS gives you daily recovery granularity for the current week, weekly recovery points for the current month, and monthly recovery points stretching back a quarter or more. It's elegant, efficient, and battle-proven.

### Beyond GFS: Modern Rotation with Restic and Borg

Modern tools handle rotation automatically. With restic, you define a retention policy and it manages the rest:

```
restic forget --keep-daily 7 --keep-weekly 4 --keep-monthly 3
```

This keeps 7 daily snapshots, 4 weekly snapshots, and 3 monthly snapshots — a GFS-like policy in a single command. Restic automatically prunes older snapshots and reclaims storage from deduplicated data. Borg offers equivalent functionality with its `borg prune` command.

## Building Your Disaster Recovery Plan

A backup strategy is only part of a complete disaster recovery plan. Here's what to consider beyond the backups themselves.

### Document Everything

Write down your backup configuration, restore procedures, contact information for your hosting providers, and the expected recovery time for each service. Store this document with your backups — not on the server that might get encrypted by ransomware.

### Define Your RPO and RTO

- **Recovery Point Objective (RPO)**: How much data can you afford to lose? If you back up once per day, your RPO is up to 24 hours of data.
- **Recovery Time Objective (RTO)**: How fast do you need to be back online? If your RTO is four hours, your restore process needs to complete within four hours.

Your backup strategy should directly serve these numbers. Daily full backups with CDP for critical data gives you near-zero RPO. Offsite snapshots in multiple regions gives you fast RTO when a data center goes dark.

### Automate Everything

Manual backups don't happen. Set up cron jobs, systemd timers, CI/CD pipeline hooks, or cloud provider schedules. Every backup should run without human intervention. Every failed backup should page someone.

### Layer Your Defense

Don't put all your trust in one tool or provider. Use on-device snapshots (like Time Machine or Linux LVM snapshots), a local NAS or backup server, and a cloud provider in a different region. If ransomware encrypts your local backups, you still have the cloud copy. If your cloud provider has an outage, you have local recovery.

### Test the Full Chain

Backup testing isn't just about restoring files. Test the entire chain: can you provision new infrastructure, install your application, restore the database, and verify data integrity? Time the process. Improve it. Repeat yearly.

## Conclusion: Test Your Backups Today

We covered a lot of ground. You now understand the four categories of data loss that threaten every application you build. You know the 3-2-1 backup strategy and how to apply it whether you're running a laptop, a cloud infrastructure, or a Laravel application on a shared host. You can choose between full, incremental, differential, and CDP backups with confidence. You know about modern tools like restic, Borg, spatie/laravel-backup, and AWS Backup. You understand retention policies and the Grandfather-Father-Son rotation scheme.

But none of it matters if you don't take action.

Data loss is not a question of if — it's a question of when. The companies that survive data loss aren't the ones with the fanciest infrastructure. They're the ones who tested their backups last week, last month, and last year. They're the ones who can restore a database in under an hour while their competitors scramble to explain why six figures of customer data is gone forever.

World Backup Day is March 31. Make every day World Backup Day. Go test your backups now.
