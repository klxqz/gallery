<?php

class photosGalleryPluginSettingsAction extends waViewAction {

    protected $plugin_id = array('photos', 'gallery');

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get($this->plugin_id);

        if (!empty($settings['albums'])) {
            $settings['albums'] = json_decode($settings['albums'], true);
        } else {
            $settings['albums'] = array();
        }
        
        if (!empty($settings['subalbums'])) {
            $settings['subalbums'] = json_decode($settings['subalbums'], true);
        } else {
            $settings['subalbums'] = array();
        }

        $album_model = new photosAlbumModel();
        $albums = @$album_model->getAlbums();
        $this->view->assign('albums', $albums);
        $this->view->assign('settings', $settings);
    }

}
