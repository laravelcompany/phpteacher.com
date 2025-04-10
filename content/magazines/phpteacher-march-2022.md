---
title: PHP Teacher magazine - March 2022
publishDate: 2022-03-01 00:00:00
description: Welcome to this issue of monthly PHP Teacher magazine, a magazine dedicated to helping PHP developers hone their skills and build more effective and resilient applications. This month, we delve into several key areas essential for any modern developer.
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - march
  - 2022
---

Welcome to this issue of monthly PHP Teacher magazine, a magazine dedicated to helping PHP developers hone their skills and build more effective and resilient applications. This month, we delve into several key areas essential for any modern developer.
First, we explore the often-overlooked but critically important topic of data backups. Our article, "Backups for the Busy Developer," will guide you through various backup strategies, including the 3-2-1 rule, different backup methods like full and differential backups, and the importance of regular testing to ensure your data is safe.
Next, we venture into the world of hardware with a practical guide to "Hacking Your Home with a Raspberry Pi: Data Storage." Learn how to connect an accelerometer sensor to your Raspberry Pi and store the sensor data in a database, turning your Pi into a powerful home monitoring system. You will find a guide to setting up the hardware and using a C++ program to record the data.
Finally, we tackle the complex yet crucial topic of software licensing in "A Journey Through Software Licensing." This article traces the history of licensing, from the free-sharing ethos of early computing to the structured legal landscape of today, explaining different types of licenses such as copyleft, permissive, and commercial and their implications for your projects.
This issue aims to provide you with actionable knowledge and practical advice to improve your coding practices and broaden your understanding of the world around software development.

## Backups for the Busy Developer**


Backups are a bit like that spare tyre in your car—you don’t think about it much, but when you need it, you *really* need it. We all know we should back up our data, but it's easy to let it slide, especially with the pressures of deadlines and feature requests. Let's be honest, how many times have you thought about backups only *after* something has gone horribly wrong? This article will give you some insights into why backups are essential, some common methods, and how you can implement them.

World Backup Day on March 31st is a great reminder to make backups a priority. But in reality, for us developers, every day should be World Backup Day. Data loss can happen in many ways, from human error (accidentally deleting that critical database table), file corruption, hardware failures, or even site-related disasters. The consequences can range from lost productivity to significant financial costs for companies.

So what exactly *is* a backup? Simply put, it's a copy of your data stored somewhere else, allowing you to restore it in case of loss. It’s a crucial part of any disaster recovery plan. However, not all backups are created equal, and some data may not be completely recoverable. Caching layers where data is never written to disk present a particular challenge in this regard.

There are several strategies for implementing backups, such as the **3-2-1 rule**: having three copies of your data, on two different local devices, and one offsite backup. This ensures that you can recover from most eventualities. For instance, you might have one copy on your local machine, another on a NAS in your home, and a third stored in the cloud. For virtual servers, the offsite backup should ideally be on a different cloud provider.

Here’s a simple ASCII art representation of the 3-2-1 backup strategy:

```
    [Local Machine] --> [Local NAS]
          \
           --> [Offsite Cloud]
```

Another aspect to consider is **retention time**, or how long you keep your backups. A good rule of thumb is about three months. There are a variety of backup methods, such as continuous data protection (CDP), full backups each day, or a Grandfather-Father-Son system. Full backups, where you copy *all* data, are simple for recovery but can be resource-intensive. Differential backups, on the other hand, store changes since the last full backup, making them quicker and smaller but require both the full and the last differential backups for a full recovery.

Here's a brief overview of these methods:

*   **Continuous Data Protection (CDP):** Restores data at any point in time. Ideal, but resource-intensive.
*   **Full Backups:** A complete copy of your data.
*   **Differential Backups:** Only copies changes since the last full backup.
*   **Grandfather-Father-Son:** A strategy involving full, weekly, and incremental backups.

Finally, the most important part—test your backups! Regularly testing the backups is an essential step. As the saying goes, a backup you haven't tested is not a backup.

Backups don't have to be complicated. The key is to make them a habit. Start with the 3-2-1 rule, pick a suitable backup method, and most importantly, regularly test your backups. Doing so can save you countless hours of work and significant stress.

## Hacking Your Home with a Raspberry Pi: Data Storage**

By Stefan

The Raspberry Pi is more than just a mini-computer; it's a versatile tool for home automation and experimentation. In this article, we’ll build on previous steps to explore how to connect an accelerometer sensor to your Pi and store its data in a database.

First, you'll need an accelerometer sensor, like the Keyes MMA8452Q. Connect it to the Pi's GPIO bus using a ribbon cable with female pins. The connection details are as follows:

```
Accelerometer Pin   | Raspberry Pi GPIO Pin
--------------------|-----------------------
Power (3.3V or +)     | 3.3V (pin 1)
Data Signal (SDA)    | SDA [GPIO2] (pin 3)
Clock Signal (SCL)   | SCL [GPIO3] (pin 5)
Ground (GND or -)    | GND (pin 9)
```

After wiring the sensor to the Pi, you’ll need to remotely log into your Pi via SSH:

```bash
ssh pi@raspberrypi.local
```

Update the Pi’s packages before continuing:

```bash
sudo apt update
sudo apt upgrade
```
Now, let's create a database to store the accelerometer data. In this case, a database called `AccelerometerData` will be set up with a table named `accelerometer_data`. The table will hold a timestamp and the x, y, and z-axis readings. Log into the MySQL CLI as superuser:

```bash
sudo mysql
```
Create a user for the database called 'accelerometer' and grant it privileges:

```sql
CREATE USER 'accelerometer'@'localhost' IDENTIFIED BY 'accelerometer';
GRANT ALL PRIVILEGES ON AccelerometerData.* TO 'accelerometer'@'localhost';
FLUSH PRIVILEGES;
```

After this, the database can be created using a tool like Adminer. The `accelerometer_data` table will have columns like `id`, `created`, `axis_x`, `axis_y`, and `axis_z`.

Here’s a SQL script to create this table:

```sql
CREATE TABLE `accelerometer_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `axis_x` float NOT NULL,
  `axis_y` float NOT NULL,
  `axis_z` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1;
```

To read the accelerometer data, we need a C++ program that can communicate with the sensor and the MySQL database. We'll need libraries for this purpose:

```bash
sudo apt install default-libmysqlclient-dev libmysqlcppconn-dev
sudo apt install i2c-tools
```

The program will use the I2C bus to communicate with the accelerometer. The program uses a loop to read data every 100 milliseconds and store the last 60 seconds worth of data. Here is a simplified example of the main function in C++ to read and write to the DB:
```cpp
int main() {
  MYSQL *connection, mysql;
  MYSQL_RES *result;
  int query_state;
  float x,y,z;
  int fileDescriptor;
  char bus[] = "/dev/i2c-1";

  if((fileDescriptor = open(bus, O_RDWR)) < 0) {
      printf("Failed to open the bus. \n");
      exit(1);
  }

  mysql_init(&mysql);
  connection = mysql_real_connect(&mysql, "localhost", "accelerometer", "accelerometer", "AccelerometerData", 0, 0, 0);
  while (1) {
      getAccelerometerData(fileDescriptor, x, y, z);
      string accelerometer_insert_query = "INSERT INTO accelerometer_data (axis_x, axis_y, axis_z) VALUES ("
          + std::to_string(x) + ", " + std::to_string(y) + ", " + std::to_string(z) + ")";

      query_state = mysql_query(connection, accelerometer_insert_query.c_str());

      usleep(100000);
  }
  mysql_close(&mysql);
  return 0;
}
```

To automatically run the data logging, a Unix service can be set up.  Create a file named `accelerometerdatalogging.service`:

```
[Unit]
Description=Accelerometer Data Logging Service
After=multi-user.target

[Service]
ExecStart=/root/log_accelerometer_data
StandardOutput=null

[Install]
WantedBy=multi-user.target
Alias=accelerometerdatalogging.service
```

Copy the executable to /root and the service file to `/lib/systemd/system`, enable the service and start it:

```bash
sudo cp log_accelerometer_data /root
sudo cp accelerometerdatalogging.service /lib/systemd/system
sudo systemctl enable accelerometerdatalogging.service
sudo systemctl start accelerometerdatalogging.service
```
With this setup, the Pi will be continuously recording accelerometer data, ready for the next part of the project.

## A Journey Through Software Licensing**


Software licensing is a complex, often misunderstood, area of software development. But if you care about the future and usage of your code, then licenses are necessary. This article will explore the history of software licensing, from its free-spirited beginnings to the structured legal landscape we have today.

Back in the early days of computing, around the 1950s and 60s, software was seen as a free exchange of information.  At places like MIT, where machines such as the TX-0 were being explored, software was shared freely among the “hackers”.  The ethos was that all information should be free.  Software was often kept in drawers and freely shared and modified.

However, this free-for-all didn’t last. As the industry developed and money started to be made, things began to change. The commercialization of software started in the 1970s, when Ed Roberts, founder of MITS, began charging for Altair BASIC. This led to the birth of software piracy and conflict in the community.

The development of Unix also had a significant influence.  Richard Stallman announced the GNU Project in 1983, trying to create a free Unix-compatible operating system.  In 1985, the Free Software Foundation was established along with the idea of “copyleft”, which is a license that requires that modifications to the code also be licensed as open source.  Stallman released the GPL v1 in 1989 as a singular license.

Today, we have three main models for software licensing:
* **Copyleft:** Always free and must be shared (e.g., GNU GPL).
* **Permissive:** Aims for ease of use and development. (e.g., MIT, BSD).
* **Commercial:**  Software is handled as the creator sees fit. (e.g., Proprietary Licenses).

The Open Source Initiative helped to codify what Open Source really meant.  However, in the 2000s, many developers began to favour ease of use over strict licensing, with many skipping licenses altogether. This is what is sometimes called "Post Open Source," where there is less concern about the legal aspects.  Some licenses like the WTFPL (Do What the Fuck You Want to Public License) even explicitly encourage such an approach.

Here's a simplified representation of how software licensing has evolved:

```
      Free Software  --> Commercial --> Open Source --> Post Open Source
    (Early Computing)    (1970s)        (1980s-2000s)      (2000s-Present)
```
The licensing of software is a crucial part of software development, but it’s an area that often confuses developers.  Understanding the different types of licenses and their implications is crucial for both developers and those using the software. Neglecting this could lead to legal issues down the line. Even in 2022, discussions about licensing and GPL continue and reveal the lack of knowledge in the community about these topics.

So, while licensing may seem like a headache, it is essential to understand the history behind it and how to apply it. Software development is a collaborative process. How you license your code affects everyone involved, and that is why understanding the various software licenses is very important.

## References
* [Wikipedia](https://en.wikipedia.org/wiki/Software_license)
* [Open Source Initiative](https://opensource.org/)
* [GNU Project](https://www.gnu.org/)
* [WTFPL](https://en.wikipedia.org/wiki/WTFPL)