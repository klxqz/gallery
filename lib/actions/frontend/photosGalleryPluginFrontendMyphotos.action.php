<?php

class photosGalleryPluginFrontendMyphotosAction extends waViewAction {

    protected $plugin_id = array('photos', 'gallery');

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get($this->plugin_id);

        $lazy = !is_null(waRequest::get('lazy'));
        if (!$lazy) {
            $this->setLayout(new photosDefaultFrontendLayout());
        } else {
            $this->setTemplate('FrontendPhotos');
        }

        $photos_per_page = wa('photos')->getConfig()->getOption('photos_per_page');
        $limit = $photos_per_page;

        $page = 1;

        if ($lazy) {
            $offset = max(0, waRequest::get('offset', 0, waRequest::TYPE_INT));
        } else {
            $page = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
            $offset = ($page - 1) * $photos_per_page;
        }

        $c = new photosCollection('gallery/myphotos');
        $photos = $c->getPhotos('*', $offset, $limit);
        $photos = photosCollection::extendPhotos($photos);

        $v = wa()->getVersion();
        wa('photos')->getResponse()->addJs('js/lazy.load.js?v=' . $v, true);
        wa('photos')->getResponse()->addJs('js/frontend.photos.js?v=' . $v, true);

        $storage = wa()->getStorage();
        $current_auth = $storage->read('auth_user_data');
        $current_auth_source = $current_auth ? $current_auth['source'] : null;
        $this->view->assign('current_auth', $current_auth, true);
        $adapters = wa()->getAuthAdapters();

        $total_count = $c->count();


        $album_model = new photosAlbumModel();
        $allowed_albums = array();

        if (!empty($settings['albums'])) {
            $settings['albums'] = json_decode($settings['albums'], true);
            $allowed_albums = $album_model->select('id,name')
                    ->where("`id` IN (" . implode(',', $settings['albums']) . ") OR `contact_id` = '" . wa()->getUser()->getId() . "'")
                    ->fetchAll();
            if (in_array(0, $settings['albums'])) {
                $allowed_albums[] = array('id' => 0, 'name' => 'Фотопоток');
            }
        }
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
        $this->view->assign(array(
            'allowed_albums' => $allowed_albums,
            'subalbums' => $subalbums,
            'photos' => $photos,
            'page' => $page,
            'offset' => $offset,
            'photos_per_page' => $photos_per_page,
            'total_photos_count' => $total_count,
            'lazy_load' => $lazy,
            'image_upload_url' => wa()->getRouteUrl('photos/frontend/imageUpload'),
            'pages_count' => floor($total_count / $photos_per_page) + 1,
            'current_auth_source' => $current_auth_source,
            'adapters' => $adapters
        ));
    }

}
