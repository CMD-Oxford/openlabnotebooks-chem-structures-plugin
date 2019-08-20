# Chemical structures plugin for WordPress

The purpose of this custom plugin is to allow Openlabnotebooks scientists to insert small molecules into their blog posts. To achieve this, we have added a custom button to the WordPress text editor that allows users to draw and import chemical structures to their posts. The plugin uses the Ketcher editor for drawing chemical structures and then the compounds get registered to ChemiReg (chemical registration system).The plugin also allows website visitors to do a similarity or substructure search against the entire website to retrieve pages that include the matching compounds.

The plugin uses RESTful API calls to ChemiReg to:

- Upload the SDF file with the compounds to ChemiReg → sgc-chem-upload.php

- Generate structure images for the blog posts → sgc-chem-retrieve-images.php

- Search for chemical structures that have been registered → sgc-chem-search.php

The plugin is designed to work with the WordPress classic TinyMCE editor. In WordPress 5, the classic text editor has been replaced with Gutenberg Editor and the plugin doesn&#39;t work unless you switch back to the old classic editor found in WordPress 4.

## **Installation**

To install the plugin, simply unzip the plugin to the WordPress plugins directory. Then, you need to visit the WordPress admin area and click on the &#39;Plugins&#39; link in the admin menu. Finally, click on the &#39;Activate&#39; link below the plugin. You will see your plugin successfully installed on the plugins page.

The plugin requires ChemiReg to be installed and activated. You can clone ChemiReg from the following GitHub repository [https://github.com/ddamerell53/ChemiRegV2](https://github.com/ddamerell53/ChemiRegV2).

## **Configuration**

You need to create a hidden config.ini file with the configuration and save it 5 directory levels back of the plugin folder. The .config.ini file should like like the below:

~~~~
[connections]
servername = 127.0.0.1
username =
password =
dbname =
chem_username =
chem_password =
api_url_login = CHEMIREG_INSTALLATION_URL/login
api_url_compounds = CHEMIREG_INSTALLATION_URL/api/compounds
api_url_upload = CHEMIREG_INSTALLATION_URL/api/uploads
api_url_image = CHEMIREG_INSTALLATION_URL/api/compound_utils/image
~~~~


You also need to edit the &#39;url&#39; variable in sgc-chem-scripts.js and tinymce-chem-structures-plugin.js and replace the 'https://openlabnotebooks.org' with your website URL.

## **Execute registration script in cron job**

You need to copy the PHP script located in /cron/sgc-chem-cron-register.php and paste it outside of the WordPress installation (probably best if you save it under a different Linux user&#39;s home folder). The script checks the database for new compounds which then registers or/and uploads them. You only need edit the file and replace the &#39;from_person@email.com&#39; and &#39;to_person@email.com&#39; with the email addresses you want to use to send notifications when a registration fails. Finally, you need add the &#39;sgc-chem-cron-register.php&#39; to cron so that it is executed and looks for changes frequently (every 5 minutes).