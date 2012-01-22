
What is this?
=============

This Drupal 6 module contains tools that I put together for some Drupal-powered sites that I manage.

Current feature list
--------------------

- A command-line script for backing up an entire Drupal installation. (bin/backup.php)
- Auditing of changed Drupal-wide variables between cron runs. (To determine if another site admin changed anything)
- Auditing of this module being enabled/disabled (to see if admins are trying to bypass it)
- Auditing of changes made to published/promoted/sticky/comments status on any node. (see what posts other admins are removing)
- Auditing of changes made to comments (see if other admins are removing comments)
- Auditing of changes made to any user's active/blocked status (see if other admins are blocking users)
- Integration with Drupal's Flag module. This module logs whenever a node or comment is flagged or unflagged.
- Integration with Drupal's Privatemsg module. This module logs whenever a private message is created or read.

Anti-abuse tools:

- See what IPs a user or a list of users came from
- See what users came from a given IP (helpful for spotting sockpuppets)
- Search chats from the  Chat Room module (http://drupal.org/project/chatroom) to investigate and verify claims of abuse, harassment, and/or spam.


Where can it be found?
----------------------
This module is currently deployed on http://www.anthrocon.org/,
http://www.saveardmorecoalition.org/, and http://www.pa-furry.org/.

Questions? Comments?
--------------------
Let me know! http://www.dmuth.org/contact

More Tools
----------
I also wrote a Drupal Template with social networking support:
https://github.com/dmuth/Dougs-Drupal-Templates


