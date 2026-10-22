# xrowForum (xrowforum)

A complete forum for an Exponential site, built on the content classes `forum`, `forum_topic` and
`forum_reply`, plus private messages between members. It adds what a community forum needs on top of plain
content objects:

- **Unread flags**: members see which forums and topics have new posts and how many; reading a topic
  removes its flag, and "mark all as read" removes them all.
- **Moderators per forum**: members of a moderator group can close and reopen topics, and edit, move and
  remove posts in the forums assigned to them.
- **User ranks**: special ranks for chosen users and ranks earned by the number of posts.
- **BB code editor**: a JavaScript toolbar for bold, italic, underline, strike, quote, code, lists, images,
  links, font color and size, plus smilies.
- **Censoring** of words you list, and **smilies** replaced by images.
- **Quick replies** created in the language of the topic.
- **View counts** and topic states (new, old, hot, closed).
- **Abuse reports** mailed to the site's administrator.
- **Private messages**: inbox, outbox, search, sorting, friends, blocked users, friend requests and
  optional mail notifications.
- **Birthdays**: a `birthday` datatype for the user class and today's birthdays in the statistics.
- An admin tab, **xrowForum**, with forum statistics, moderator assignment, ranks and settings.

It is for site builders who want a forum inside their Exponential site rather than a separate forum
application. Expect to adapt the templates to your design: the extension ships working but plain templates.

## Requirements

- Exponential 6.x (the legacy PHP kernel)
- PHP 8.0 or later
- The **ezjscore** extension (shipped with Exponential), for jQuery in the front end
- A MySQL or MariaDB database (the tables are created from MySQL scripts)
- The content classes `forum`, `forum_topic` and `forum_reply` (from the Exponential website interface
  package or your own)

## Installation

The extension directory must be named `xrowforum`.

### 1. Get the code

With Composer:

```bash
composer require se7enxweb/xrowforum
```

or by hand:

```bash
cd /path/to/exponential
git clone https://github.com/se7enxweb/xrowforum.git extension/xrowforum
```

(or unpack a [release archive](https://github.com/se7enxweb/xrowforum/releases) to `extension/xrowforum`).

### 2. Activate the extension and its design

In `settings/override/site.ini.append.php`:

```ini
[ExtensionSettings]
ActiveExtensions[]=xrowforum
```

The forum templates live in the design `xrowforum`. Add it to the design list of your public siteaccess
**and** of the admin siteaccess (the admin tab's pages are templates of that design), in
`settings/siteaccess/<siteaccess>/site.ini.append.php`:

```ini
[DesignSettings]
AdditionalSiteDesignList[]=xrowforum
```

Better still, copy the templates you want to change into your own design extension and keep this one
untouched.

### 3. Create the database tables

```bash
mysql -u <user> -p <database> < extension/xrowforum/doc/install/forum_tables.sql
mysql -u <user> -p <database> < extension/xrowforum/doc/install/pm_tables.sql
```

This creates `xrowforum_notification`, `xrowforum_viewcount`, `xrowforum_rank`, `xrowforum_pm_messages`
and `xrowforum_pm_network`. The scripts drop these tables first if they exist.

### 4. Regenerate autoloads and clear the caches

```bash
php bin/php/ezpgenerateautoloads.php -e
php bin/php/ezcache.php --clear-all
```

### 5. Extend the content classes

In Setup, Classes:

- **User**: add an attribute `birthday` of the datatype **Birthday** and an attribute `moderator` of the
  datatype **Object relations** (the forums the user moderates).
- **Forum topic**: add an attribute `closed` of the datatype **Checkbox**.

Screenshots of these steps are in `doc/install/` (`user_class.JPG`, `forum_topic_class.JPG`).

### 6. Set up the workflows

The extension brings four workflow event types: **Flag**, **Deflag**, **set Session** and
**Update modified TS**. Create these workflows (Setup, Workflows):

1. A workflow with the event **Flag**.
2. A workflow with the event **Deflag**.
3. A workflow with a **Multiplexer** limited to the classes forum topic and forum reply, for "Publishing new
   object", that runs workflow 1.
4. A workflow with the event **set Session**.
5. A workflow with a **Multiplexer** limited to forum topic and forum reply, for "Updating existing object",
   that runs workflow 4.
6. A workflow with the event **Update modified TS**.
7. Add a second **Multiplexer** to workflow 3, limited to forum topic and forum reply, for "Updating existing
   object", that runs workflow 6.

Then set the triggers (Setup, Triggers):

| Trigger | Workflow |
|---|---|
| content, publish, before | workflow 5 |
| content, publish, after | workflow 3 |
| content, read, before | workflow 2 |

The `content/read` trigger works because the extension adds `content_read` to `workflow.ini`
`[OperationSettings] AvailableOperationList[]`. Screenshots: `doc/install/workflow_3_part1.JPG`,
`workflow_3_part2.JPG`, `workflow5.JPG`, `trigger.JPG`.

### 7. Roles

1. Create a user group "Moderators" and a role "Moderator" assigned to it, with `content/edit` and
   `content/remove` (forum topic and forum reply), `content/move` and `xrowforum/close`, limited to the
   forum section or subtree. Optionally add `xrowforum/ranks` and `xrowforum/settings`.
2. Give the members' role `xrowforum/overview`, `xrowforum/create`, `xrowforum/removeflags`,
   `xrowforum/report` and `pm/*`.
3. Give the anonymous role `content/read` on the Users section, so birthdays can be shown.

Screenshots: `doc/install/member_role.JPG`, `moderator_role.JPG`.

### 8. Tell the forum where things are

Open the **xrowForum** tab in the administration interface, choose **Settings** and fill in the IDs: the
forum index node, the moderator user group object, and the class IDs of user, forum topic and forum reply.

The forum expects this tree (see `doc/install/hierarchy.jpg`):

```
forum index node (for example a folder)
└── forums (a folder per group of forums)
    └── forum
        └── forum topic
            └── forum reply
```

To use the extension's forum index page, enable the override in
`settings/siteaccess/eng/override.ini.append.php` for your siteaccess with the node ID of your forum index:

```ini
[forum_index]
Source=node/view/full.tpl
MatchFile=forum_index.tpl
Subdir=templates
Match[node]=123
```

### 9. Cronjobs

```bash
# every 5 minutes: remember the highest number of users online at once
*/5 * * * * cd /path/to/exponential && php runcronjobs.php mostuser
# every night: remove read flags older than KeepFlagDuration days
1 2 * * * cd /path/to/exponential && php runcronjobs.php cleanflags
```

## Usage

### The xrowForum tab

The tab is `[Topmenu_xrowForum]` in `settings/menu.ini.append.php`; users whose role grants
`xrowforum/settings` see it. In the admin4 design it shows the Bootstrap icon **chat-square-text** before
its name:

```ini
[Topmenu_xrowForum]
Icon=chat-square-text
NavigationPartIdentifier=ezxrownav
Name=xrowForum
URL[default]=xrowforum/overview
PolicyList[]=xrowforum/settings
```

Its left menu has four pages:

| View | What it shows |
|---|---|
| `xrowforum/overview` | Statistics: start date, topics, posts, users, users online now and in the last 24 hours, today's birthdays, the most users ever online, topics, posts and registrations per day, the newest users, topics and posts, the hottest topics and the most active users. |
| `xrowforum/permissions` | The members of the moderator group and the forums each one moderates (their `moderator` relations). |
| `xrowforum/ranks` | Create, switch on and off and delete ranks: special ranks for chosen users, or post-count ranks with the number of posts as their condition. |
| `xrowforum/settings` | Every setting of `xrowforum.ini` described below, saved to `settings/override/xrowforum.ini.append.php`. |

### Front-end views

| URL | What it does |
|---|---|
| `xrowforum/create/<NodeID>/<LanguageCode>` | Reply to a topic, in the topic's language. |
| `xrowforum/close/<NodeID>` | Close an open topic or reopen a closed one (moderators). |
| `xrowforum/removeflags` | Mark everything as read for the current user. |
| `xrowforum/report` | POST `node_id`, `type=abuse` and `comment`: mails an abuse report to `[MailSettings] EmailSender` (else `AdminEmail`). |
| `pm/inbox/<sorting>/<direction>` | The inbox; sort by `send_date`, `pm_subject` or `sender`, `asc` or `desc`. |
| `pm/outbox/<sorting>/<direction>` | The outbox; sort by `send_date`, `pm_subject`, `recipient` or `read_state`. |
| `pm/view/<messageID>` | Read a message. |
| `pm/create/<RecipientID>/<ReplyID>` | Write a message, to a user or as a reply. |
| `pm/modify/remove/<messageID>/<inbox or outbox>` | Delete a message. |
| `pm/network/<Type>/<RelatedUserID>` | Friends and blocked users: type `0` sends a friend request, `1` blocks the user. |

More on the private message module, with form examples: `doc/pm_documentation`.

### Template operators

| Operator | Example |
|---|---|
| `operator_merge( <type>, <text> )` | `{operator_merge( content, $node.data_map.message.content )}` applies BB code, smilies, censoring and `nl2br` as configured. Types: `content`, `preview`, `signature`. |
| `bbcode2xhtml`, `xhtml2bbcode` | `{$text|bbcode2xhtml()}` |
| `censoring` | `{$text|censoring()}` replaces the words of `[Censoring] CensoringList[]`. |
| `like_operator( <path_string>, <limit> )` | `{set $new_posts = like_operator( $topic.path_string )}`: the current user's unread flags below a node (up to 100 by default); `$new_posts|count` is the number of new posts. |
| `deflag_subtree( <path_string>, <limit> )` | Remove the current user's flags below a node. |
| `read_count( <object id> )`, `write_count( <object id> )` | `{set $topic_view_count = read_count( $topic.contentobject_id )}` returns the stored row (`$topic_view_count.0.viewcount`); `write_count` adds one view. |
| `request_rank( <user id> )` | `{request_rank( $owner.id )}`: the user's rank. |
| `object_by_id( <user id> )` | `{object_by_id( $owner.id )}`: the number of published topics and replies the user owns. |
| `pm_message_check( <inbox or outbox>, <state> )` | `{pm_message_check( "inbox", 0 )}`: number of unread (0) or read (1) messages; without a state, all. |
| `pm_mark_as_read( <msg_id>, <sender>, <recipient>, <send_date> )` | Mark a message as read. |
| `pm_is_inRelation( <user id>, <user2 id>, <state> )` | `{if pm_is_inRelation( $me, $other, 2 )}` is true when the users are related in that state: 0 pending, 1 friends, 2 blocked. |
| `pm_relation_fetch( <user id>, <state>, <logged_condition> )` | A user's pending requests (0), friends (1) or blocked users (2); `logged_condition` is `all`, `online` or `offline`. |

## Configuration

Change settings in the **Settings** page of the tab, or copy blocks into
`settings/override/xrowforum.ini.append.php`:

```ini
[ClassIDs]
ForumTopic=38
ForumReply=39
User=4

[IDs]
# node ID of the forum index
ForumIndexPageNodeID=123
# object ID of the moderator user group
ModeratorGroupObjectID=456

[GeneralSettings]
Rankings=enabled
WordToImage=enabled
Censoring=enabled
SignatureImage=enabled
TopicsPerPage=20
PostsPerPage=20
PostHistoryLimit=20
# posts that make a topic "hot"
HotTopicNumber=50
StatisticLimit=5
# days the cleanflags cronjob keeps flags
KeepFlagDuration=30

[BB-Codes]
BBCodeList[]
BBCodeList[big]=enabled
BBCodeList[italic]=enabled
BBCodeList[underline]=enabled
BBCodeList[quote]=enabled
BBCodeList[code]=enabled
BBCodeList[list]=enabled
BBCodeList[strike]=enabled
BBCodeList[img]=enabled
BBCodeList[url]=enabled
BBCodeList[fontcolor]=enabled
BBCodeList[fontsize]=enabled
BBCodeList[smilies]=enabled

[PrivateMessaging]
AllowSendingNotificationMails=true
PmsPerPage=10
# where the recipient name suggestions come from
SelectableUserPathString[]=/1/5/12

[Censoring]
CensoringList[]
CensoringList[]=badword
```

`[MostUserOn] Amount` and `Date` are written by the `mostuser` cronjob. Other files:

- `wordtoimage.ini`: the smilies (`ReplaceText[]` and `ReplaceIcon[]` pairs) and their image directory.
- `BBCodeParser.ini`: options of the BB code parser (quote style, filters).

The **Settings** page and the `mostuser` cronjob write to `settings/override/xrowforum.ini.append.php`; the
web server and the cron user need write access to it.

## Permissions

| Function | Lets the user |
|---|---|
| `xrowforum/settings` | see the xrowForum tab and change the settings |
| `xrowforum/overview` | see the statistics |
| `xrowforum/permissions` | see the moderators page |
| `xrowforum/ranks` | manage ranks |
| `xrowforum/create` | reply to topics |
| `xrowforum/close` | close and reopen topics |
| `xrowforum/removeflags` | mark everything as read |
| `xrowforum/report` | report abuse |
| `pm/inbox`, `pm/outbox`, `pm/view`, `pm/create`, `pm/modify`, `pm/network` | use private messages |

## Upgrading

Replace the extension directory, regenerate the autoloads and clear the caches. Version 1.0.0 needs no
database changes compared with the code published before. The kernel patches the old installation notes
mentioned (cookies and the logged-in user list) have long been part of the kernel; do not apply them.

## Known limits

- Private message recipients are found by name: user names should be unique.
- The tables are created by MySQL scripts only; other databases need their own scripts.
- The front end loads two YUI 2 files (`yahoo-min.js`, `cookie-min.js`) and Shadowbox; the templates are
  plain and meant to be adapted.
- The BB code parser turns `http://www.example.com` into a link, but not `www.example.com` alone.
- Do not switch on template debug output on the forum pages: it breaks the BB code editor's JavaScript.
- The `birthday` datatype is the same one the separate `birthday` extension provides; activate only one of
  them.

## Troubleshooting

- **The xrowForum tab shows an empty page**: add `xrowforum` to the admin siteaccess's
  `AdditionalSiteDesignList[]`.
- **No flags appear**: check the workflows and triggers of step 6 and that `ForumIndexPageNodeID` is set.
- **Statistics are empty**: fill in the IDs on the Settings page.
- **Settings are not saved**: make `settings/override/` writable for the web server.

## Contributing

Bug reports and pull requests are welcome at
[github.com/se7enxweb/xrowforum](https://github.com/se7enxweb/xrowforum/issues).

## License

GNU General Public License v2.0; see [LICENSE](LICENSE).

## Credits and history

xrowForum was written by xrow GmbH, Hannover, Germany, and first released on projects.ez.no in July 2010 for
eZ Publish 4.2 to 4.5, the PHP content management system that its creator discontinued after about 18
years; the private message module followed in 2011. It includes the birthday datatype by Georg W. Franz,
the BB code template operator by Vathanan Kumarathurai (based on PEAR's HTML_BBCodeParser by Stijn de
Reede) and Shadowbox. 7x maintains it for Exponential.
