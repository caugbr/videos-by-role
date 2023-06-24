# Videos by role
A Wordpress plugin to organize videos in categories and restrict access to them by user roles.

## First things
Once installed, VbR creates a post type called 'video' and a taxonomy called 'video category'. This taxonomy is implicit and you cannot create terms directly. You will need to choose a single category before saving your video.

The first step is to create some new user roles via the VbR settings page. Each created role will have a relative category term and a native capability that allows you to watch tagged videos. If you've created other roles before, their specific capabilities will be available.

## The 'Import video' meta box
The providers you can use will be visible above the text box.
Just go to video page, copy the entire URL and paste into our text box. Click out to fire the onChange event.
The fields and button bellow the text box will be available in a few seconds, then you can use the original title, thumbnail and embed the video into your post.

## Access to the videos
Only admins and users of the roles we created will be able to watch the videos. Administrators will have access to all of them, but other users will only see what their capabilities allow.