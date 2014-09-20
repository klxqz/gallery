<?php

class photosGalleryPluginFrontendAddAlbumAction extends waViewAction {

    protected $plugin_id = array('photos', 'gallery');

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get($this->plugin_id);

        $album_model = new photosAlbumModel();
        $subalbums = array();
        if (!empty($settings['subalbums'])) {
            $settings['subalbums'] = json_decode($settings['subalbums'], true);
            $subalbums = $album_model->select('id,name')
                    ->where("`id` IN (" . implode(',', $settings['subalbums']) . ") OR `contact_id` = '" . wa()->getUser()->getId() . "'")
                    ->fetchAll();
            if (in_array(0, $settings['subalbums'])) {
                $subalbums[] = array('id' => 0, 'name' => 'Фотопоток');
            }
        }
        $this->view->assign('subalbums', $subalbums);
        $template_path = wa()->getAppPath('plugins/gallery/templates/actions/frontend/AddAlbum.html', 'photos');
        $this->setTemplate($template_path);
    }

}
