---
title : "Basics"
description : "Getting Started"
---
If you want to follow along by writing code, start by downloading a code editor. I recommend
[Visual Studio Code](https://code.visualstudio.com/) or [Sublime Text](https://www.sublimetext.com/).
Next, create a new file in your editor called `basics.php` and save it anywhere on your computer, like a folder
in your documents called `phpapprentice`. Now, we can write some PHP.

All PHP files must start with a `<?php` tag unless it is for a html template.
(We will learn about html templates later.)
```php
<?php

echo "Hello World!\n";
```

There is a lot going on in the above code so let's work through it.

First, the echo keyword tells PHP to output some text.
```php
echo "I am some text\n";
```

Second, PHP stores text in strings. To write a string, you surround letters with single or double quotes.
Double quoted strings can hold special characters like `\n` which tells PHP to start a new line.
```php
echo "I am a string on a new line\n";
```

Third, all lines of code in PHP must end in a semi-colon.
```php
echo "No semi-colon is a no-no\n";
```

Using semi-colons means we can write multiple statements on one line.
```php
echo "PHP"; echo " Apprentice\n";
```

To execute the code you have written, make sure you have [PHP installed](/installing-php.html).
Then, open a terminal app, either Terminal on MacOS or Powershell on Windows. In the terminal,
open the folder where you created the `basics.php` file using `cd`. For example, on Windows run `cd C:\%userprofile%\Documents\phpapprentice` and on Mac run `cd ~/Documents/phpapprentice`. Finally, you can execute the file by running `php basics.php`.

In your terminal, you should see:
```bash
Hello World!
I am some text
I am a string on a new line.
No semi-colon is a no-no
PHP Apprentice
```

With any code in future chapters, I recommend writing a PHP file for it.
It is a great way to get some practice with the language.


### **General Setup and Editor Questions:**

#### 1. What are two recommended code editors for writing PHP code?
- The two recommended code editors are:
  - **Visual Studio Code (VS Code)** – A powerful, customizable editor with support for AI-powered features and a wide range of extensions.
  - **Sublime Text** – A fast and lightweight editor known for its performance and modern features like GPU rendering and improved syntax highlighting.

#### 2. How do you download and install Visual Studio Code or Sublime Text?
- **Visual Studio Code**:
  - Visit [https://code.visualstudio.com/](https://code.visualstudio.com/)
  - Download the appropriate version for your operating system (Windows, macOS, or Linux).
  - Follow the installation instructions for your OS.
- **Sublime Text**:
  - Visit [https://www.sublimetext.com/](https://www.sublimetext.com/)
  - Download Sublime Text 4 for your platform.
  - Install it using the provided installer or package manager.

#### 3. Why is it important to create a dedicated folder for your PHP projects?
- Creating a dedicated folder helps organize your files and keeps all related project files in one place. This makes it easier to manage, locate, and execute scripts when working in the terminal or command prompt.

#### 4. How do you create a new file called `basics.php` in your code editor?
- Open your code editor (VS Code or Sublime Text).
- Navigate to or open the folder where you want to store your PHP files (e.g., `phpapprentice`).
- Use the file menu or shortcut (like Ctrl+N or Cmd+N) to create a new file.
- Save the file as `basics.php`.

---

### **PHP Syntax and Concepts:**

#### 5. What is the opening tag required at the beginning of every PHP file?
- The opening tag required at the beginning of every PHP file is `<?php`.

#### 6. What does the `echo` keyword do in PHP?
- The `echo` keyword outputs text or data directly to the screen or browser output.

#### 7. How do you display text on the screen using PHP?
- You can use the `echo` statement followed by a string, like:
  ```php
  echo "Hello World!\n";
  ```

#### 8. What is a string in PHP, and how is it defined?
- A string in PHP is a sequence of characters used to represent text. It is defined by enclosing the text within either single quotes (`'`) or double quotes (`"`).

#### 9. What is the difference between single quotes and double quotes when defining strings in PHP?
- Double-quoted strings allow for **special characters** (like `\n` for newline) and variable interpolation.
- Single-quoted strings treat everything literally and do not process special characters or variables.

#### 10. What special character can be used inside double quotes to create a new line in PHP?
- The special character `\n` is used inside double quotes to insert a new line in the output.

#### 11. Why is it necessary to end each PHP statement with a semicolon?
- In PHP, the semicolon (`;`) marks the end of a statement. Omitting it will result in a syntax error because PHP won’t know where one instruction ends and the next begins.

#### 12. Is it possible to write multiple PHP statements on the same line? If yes, how?
- Yes, multiple PHP statements can be written on the same line by separating them with semicolons:
  ```php
  echo "PHP"; echo " Apprentice\n";
  ```

#### 13. What happens if you forget to add a semicolon at the end of a PHP statement?
- PHP will throw a **syntax error**, and the script will fail to run until the missing semicolon is added.

---

### **Running PHP Code:**

#### 14. How do you execute a PHP script from the terminal or command prompt?
- Open a terminal or command prompt.
- Navigate to the directory containing your PHP file using the `cd` command.
- Run the script using the `php` command followed by the filename:
  ```
  php basics.php
  ```

#### 15. What command would you use to navigate to the `phpapprentice` folder in the terminal?
- On Windows:
  ```
  cd C:\%userprofile%\Documents\phpapprentice
  ```
- On Mac/Linux:
  ```
  cd ~/Documents/phpapprentice
  ```

#### 16. What should you see as the output after running the `php basics.php` command?
- You should see the following output:
  ```
  Hello World!
  I am some text
  I am a string on a new line
  No semi-colon is a no-no
  PHP Apprentice
  ```

#### 17. Why is it necessary to have PHP installed before running PHP scripts?
- PHP is an interpreted language, so the PHP interpreter must be installed on your machine to execute `.php` files. Without it, the system won’t recognize or process PHP code.

---

### **Practice and Application:**

#### 18. What is the benefit of writing and testing PHP code as you read through tutorials or chapters?
- Writing and testing code while learning helps reinforce concepts, improves understanding, and allows for immediate feedback and experimentation.

#### 19. What is one way to practice PHP programming effectively while learning?
- One effective way is to write small programs or modify existing examples to see how changes affect the output. For example, experimenting with different strings, adding more `echo` statements, or changing variable values.

#### 20. How can you modify the `echo` statements in the example to experiment with different outputs?
- You can change the text inside the strings, add more `\n` characters for new lines, combine multiple strings, or try using single quotes instead of double quotes to see how the output changes.

