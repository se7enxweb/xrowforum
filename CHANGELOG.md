# Changelog

## 1.0.0 (2026-10-22)

First tagged release, for Exponential 6.x and PHP 8.

- Updated: The xrowForum admin tab (`[Topmenu_xrowForum]`) shows the Bootstrap icon `chat-square-text` in
  the admin4 design (`Icon=chat-square-text` in `settings/menu.ini.append.php`).
- Fixed: The template operators, the four workflow event types, the birthday datatype and the BB code
  parser have `__construct()` constructors; PHP 8 no longer ran their class-named constructors.
- Fixed: The private message views and the ranks page cast IDs to integers and check the sort column,
  sort direction and box name before using them in SQL.
- Updated: A modern README.md describes features, installation (Composer and by hand, tables, classes,
  workflows, roles, cronjobs), the admin tab, every view, the template operators, the settings,
  permissions, known limits and troubleshooting. doc/README and doc/install/INSTALL point to it.
- Added: composer.json (`se7enxweb/xrowforum`), extension.xml and a LICENSE file (GNU GPL v2.0, as the
  file headers state).
- Updated: ezinfo.php (class `xrowforumInfo`) states the version, copyright, license and website.

## Before 1.0.0

- 2011-05-01: Private message module; ready for eZ Publish 4.5.
- 2011-04-22: Cronjob to clean the `xrowforum_notification` table; the cronjob part for the most users
  online was renamed to `mostuser`.
- 2010-07-08: Released on projects.ez.no.
