# GitLabAutoSync

> Just programming.

The script make easy the sync code of GitLab to any server.

### Config info

##### httptoken

> https://gitlab.com/-/profile/personal_access_tokens

The script require a token of personal access with these required permissions:

- read_api
- read_repository

If you want the script add a webhook automatically so is required the permission:

- api

##### projectid

> Project ID

You can found it in the home page or "General Configuration" link of the project.

##### outputdir

> Recommended the public_html of the server

It is the output dir where the repository of GitLab will synced

##### branch

> By default is *main*

You need to set the branch what will synced

### Use

1. Download the file "dist/glas.v20221025185800.zip" on your public_html
2. Unzip the file
3. Now you have 02 files (GitLabAutoSync.phar.gz and GitLabAutoSync.php)
4. If you want to use it as command:
	a. Use the command `php GitLabAutoSync.php`
	b. If is the firsttime the script will require the config info, then you can save it
	c. The script will sync the repository of GitLab into the output_dir (normally your public_html path)
5. If you want to use it as a web
	a. Access to https://yourweb.com/GitLabAutoSync.php (replace yourweb.com to your domain)
	b. If is the firsttime the web will require the config info, it will be saved (then you need to refresh the page)
	c. The web ask you if you want to add a webhook in GitLab Project for auto-sync, is recommended set it true (then you need to refresh the page)
	d. Anytime you access to this web, the repository of GitLab will be synced into the output_dir (normally the same public_html path)
