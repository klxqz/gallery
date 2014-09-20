<?php

class photosGalleryPluginFrontendImageUploadController extends waJsonController {

    /**
     *
     * @var photosPhotoModel 
     */
    protected $model;
    protected $plugin_id = array('photos', 'gallery');

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get($this->plugin_id);

        $this->response['files'] = array();

        $this->model = new photosPhotoModel();

        //$plugin = wa()->getPlugin('gallery');


        $data = array(
            'contact_id' => wa()->getUser()->getId(),
            'status' => 1,
            'groups' => array(0),
            'source' => 'gallery',
        );



        if ($settings['need_moderation']) {
            $data['moderation'] = 0;
        } else {
            $data['moderation'] = 1;
        }
        if ($data['moderation'] <= 0) {
            $data['status'] = 0;
        }

        $this->getStorage()->close();

        if (waRequest::server('HTTP_X_FILE_NAME')) {
            $name = waRequest::server('HTTP_X_FILE_NAME');
            $size = waRequest::server('HTTP_X_FILE_SIZE');
            $file_path = wa()->getTempPath('photos/upload/') . $name;
            $append_file = is_file($file_path) && $size > filesize($file_path);
            clearstatcache();
            file_put_contents(
                    $file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0
            );
            $file = new waRequestFile(array(
                'name' => $name,
                'type' => waRequest::server('HTTP_X_FILE_TYPE'),
                'size' => $size,
                'tmp_name' => $file_path,
                'error' => 0
            ));
            try {
                $this->response['files'][] = $this->save($file, $data);
            } catch (Exception $e) {
                $this->response['files'][] = array(
                    'error' => $e->getMessage()
                );
            }
        } else {
            $files = waRequest::file('files');
            foreach ($files as $file) {
                if ($file->error_code != UPLOAD_ERR_OK) {
                    $this->response['files'][] = array(
                        'error' => $file->error
                    );
                } else {
                    try {
                        $this->response['files'][] = $this->save($file, $data);
                    } catch (Exception $e) {
                        $this->response['files'][] = array(
                            'name' => $file->name,
                            'error' => $e->getMessage()
                        );
                    }
                }
            }
        }
    }

    public function save(waRequestFile $file, $data) {

        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get($this->plugin_id);

        if (!empty($settings['albums'])) {
            $settings['albums'] = json_decode($settings['albums'], true);
        } else {
            $settings['albums'] = array();
        }
        // check image
        if (!($image = $file->waImage())) {
            throw new waException(_w('Incorrect image'));
        }

        //$plugin = wa()->getPlugin('gallery');

        $min_size = $settings['min_size'];
        if ($min_size && ($image->height < $min_size || $image->width < $min_size)) {
            throw new waException(sprintf(_w("Image is too small. Minimum image size is %d px"), $min_size));
        }

        $max_size = $settings['max_size'];
        if ($max_size && ($image->height > $max_size || $image->width > $max_size)) {
            throw new waException(sprintf(_w("Image is too big. Maximum image size is %d px"), $max_size));
        }

        $album_id = waRequest::post('album_id', -1);

        $album_model = new photosAlbumModel();
        $albums = $album_model->select('id')
                ->where("`id` IN (" . implode(',', $settings['albums']) . ") OR `contact_id` = '" . wa()->getUser()->getId() . "'")
                ->fetchAll();
        $allow_album = array();
        foreach ($albums as $album) {
            $allow_album[] = $album['id'];
        }


        if (!in_array($album_id, $allow_album)) {
            throw new waException('Недоступный для загрузки альбом');
        }
        if ($album_id > 0) {
            $data['album_id'] = $album_id;
        }

        $id = $this->model->add($file, $data);
        if (!$id) {
            throw new waException(_w("Save error"));
        }
        $tag = $settings['assign_tag'];
        if ($tag) {
            $photos_tag_model = new photosPhotoTagsModel();
            $photos_tag_model->set($id, $tag);
        }
        return array(
            'name' => $file->name,
            'type' => $file->type,
            'size' => $file->size
        );
    }

    public function display() {
        $this->getResponse()->sendHeaders();
        echo json_encode($this->response);
    }

}
