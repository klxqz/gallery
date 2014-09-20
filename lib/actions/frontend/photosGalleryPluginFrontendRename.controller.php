<?php

class photosGalleryPluginFrontendRenameController extends waJsonController {

    public function execute() {
        $types = array('name', 'description');
        $id = waRequest::post('id');
        $type = waRequest::post('type');
        $val = waRequest::post('val');

        $model = new photosPhotoModel();
        if (in_array($type, $types)) {
            $model->updateById($id, array($type => $val));
        }
        
        if($type == 'delete') {
            $model->deleteById($id);
        }
    }

}
