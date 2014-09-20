<?php

class photosGalleryPluginFrontendSaveAlbumController extends waJsonController {

    public function execute() {
        $album_model = new photosAlbumModel();
        $album_rights_model = new photosAlbumRightsModel();
        $data = waRequest::post('album', array());
        $data['status'] = 1;
        $data['url'] = $album_model->suggestUniqueUrl(photosPhoto::suggestUrl($data['name']));
        $data['group_ids'] = null;
        $data['type'] = 0;
        $id = $album_model->add($data);
        $album_rights_model->setRights($id, 0);

        if ($data['parent_id']) {
            $parent = $album_model->getById($data['parent_id']);
            $child = $album_model->getFirstChild($parent['id']);
            $album_model->move($id, $child ? $child['id'] : 0, $parent['id']);
        }
    }

}
