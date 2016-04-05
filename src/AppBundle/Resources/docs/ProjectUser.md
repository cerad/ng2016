So a project user is a combo of the standard Symfony user object as well as the ProjectPerson for the default project.

One reason for this approach is to have project specific roles which are needed on pretty much every request to render menus.

It needs to be an actual object for the Symfony security system to process.
Eliminating the security component would require a custom security listener as well as login control stuff.
Might be worth looking into later but for now keep it.

Will implement array access in keeping with the array oriented approach for other code.

MariaDB [ng2014]> describe users;
+-----------------------------+--------------+------+-----+---------+----------------+
| Field                       | Type         | Null | Key | Default | Extra          |
+-----------------------------+--------------+------+-----+---------+----------------+
| id                          | int(11)      | NO   | PRI | NULL    | auto_increment |
| person_guid                 | varchar(40)  | YES  |     | NULL    |                |
| person_status               | varchar(20)  | YES  |     | NULL    |                |
| person_verified             | varchar(20)  | YES  |     | NULL    |                |
| person_confirmed            | tinyint(1)   | NO   |     | NULL    |                |
| username                    | varchar(255) | NO   |     | NULL    |                |
| username_canonical          | varchar(255) | NO   | UNI | NULL    |                |
| email                       | varchar(255) | NO   |     | NULL    |                |
| email_canonical             | varchar(255) | NO   | UNI | NULL    |                |
| email_confirmed             | tinyint(1)   | NO   |     | NULL    |                |
| salt                        | varchar(255) | NO   |     | NULL    |                |
| password                    | varchar(255) | NO   |     | NULL    |                |
| password_hint               | varchar(20)  | YES  |     | NULL    |                |
| roles                       | longtext     | NO   |     | NULL    |                |
| account_name                | varchar(80)  | YES  |     | NULL    |                |
| account_enabled             | tinyint(1)   | NO   |     | NULL    |                |
| account_locked              | tinyint(1)   | NO   |     | NULL    |                |
| account_expired             | tinyint(1)   | NO   |     | NULL    |                |
| account_expires_at          | datetime     | YES  |     | NULL    |                |
| account_created_on          | datetime     | YES  |     | NULL    |                |
| account_updated_on          | datetime     | YES  |     | NULL    |                |
| account_last_login_on       | datetime     | YES  |     | NULL    |                |
| credentials_expired         | tinyint(1)   | NO   |     | NULL    |                |
| credentials_expire_at       | datetime     | YES  |     | NULL    |                |
| password_reset_token        | varchar(255) | YES  |     | NULL    |                |
| password_reset_requested_at | datetime     | YES  |     | NULL    |                |
| password_reset_expires_at   | datetime     | YES  |     | NULL    |                |
| email_confirm_token         | varchar(255) | YES  |     | NULL    |                |
| email_confirm_requested_at  | datetime     | YES  |     | NULL    |                |
| email_confirm_expires_at    | datetime     | YES  |     | NULL    |                |
| person_confirm_token        | varchar(255) | YES  |     | NULL    |                |
| person_confirm_requested_at | datetime     | YES  |     | NULL    |                |
| person_confirm_expires_at   | datetime     | YES  |     | NULL    |                |
+-----------------------------+--------------+------+-----+---------+----------------+