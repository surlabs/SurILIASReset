### Installation steps
1. Create subdirectories, if necessary for /Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
2. In /Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
3. Then, execute:
```bash
git clone https://github.com/surlabs/SurILIASReset.git ./SurILIASReset
cd SurILIASReset
git checkout main
```
3. SurILIASReset uses the ILIAS composer autoloader functionality so, after installing or update the plugin, ensure you run on the ILIAS root folder
```bash
composer du
npm install
php setup/setup.php update
```
***
**Please ensure you don't ignore plugins on composer.json**
***
