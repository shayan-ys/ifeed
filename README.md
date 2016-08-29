# iFeed
Iterating Feeds For WordPress

## How iFeed Works
- Each iFeed can be either a WordPress query or set of manual posts.
- In Auto mode: You can set tags, excluded tags, categories, and time schedule you want to publish each post, and view the schedule right away at the side of your iFeed edit page.
- In Manual mode: You can add as much post as you want, set the date and hour you want to publish each post, or even edit Auto generated posts (of course it will switch to Manual mode then).
- When times come according to schedule you set, iFeed-Refresher will add the post to your sites feed, and will pop out previously online post. (So always only one post will be in your feed, although it can increase in next version of iFeed.) ,***This makes it perfect for sites have robot to read your feed.***

## Installation Notes

- Can be downloaded from WordPress repository or from this link.
- On plugin activation, a refresher page will be created automatically (**on plugin deactivate, it will be deleted**) its URL will be visible in plugin's settings page.
- This plugin uses a table in your database, it will create it automatically and on plugin uninstallation, it will be deleted. (be careful about your data)
- To make this plugin work fully, you should set a cronjob to call the refresher URL (illustrated in settings page) at least every hour.

## Report probable issues on WordPress developers community or Github Repo

### Copyright
Developed by (Shayan Ys)[http://www.shayanys.com] at (BE360)[http://www.be360.ir] private company for (Chetor.com)[http://www.chetor.com] project. All right reserve.