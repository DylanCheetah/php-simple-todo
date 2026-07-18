# Lesson 01: Prerequisites

In this tutorial series we will be developing a simple todo list website using PHP, SQL, HTML, CSS, and HTMX. Before we begin, we will need to install the software we will need:
01. install Visual Studio Code from https://code.visualstudio.com/download
02. start Visual Studio Code
03. click the Extensions tab
04. search for "php intelephense"
05. install the PHP Intelephense extension by Intelephense
06. install PHP:
    * Windows:
        01. download a thread-safe build of PHP from https://www.php.net/downloads.php
        02. extract the contents of the zip file to C:\php
        03. start the Settings app
        04. navigate to System > About
        05. click Advanced System Settings
        06. click Environment Variables
        07. click PATH in the bottom list
        08. click Edit
        09. click New
        10. type "C:\php"
        11. press Enter
        12. click Ok on all 3 dialogs
        13. create "C:\php\php.in" with the following content:
        ```
        extension=pdo_mysql
        ```
    * Linux:
        01. open a terminal
        02. execute `sudo apt install php php-mysql`
    * Mac:
        01. open a terminal
        02. execute `curl -o- https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh | bash`
        03. execute `brew install php@8.5`
        04. execute `brew link --force --overwrite php@8.5`
07. install MariaDB:
    * Windows: install MariaDB from https://mariadb.org/download/
    * Linux: install/update MariaDB via your package manager
    * Mac: install/update MariaDB via your package manager
