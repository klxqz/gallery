<?php

/**
 * Json action for getting one photo
 * @see photosFrontendPhotoAction
 */
class photosGalleryPluginFrontendLoadPhotoController extends waJsonController
{
    private $photo_model;

    public function execute()
    {
        $url = waRequest::param('url');
        $this->hash = 'gallery/myphotos';
        if (!$url) {
            throw new waException(_w('Page not found', 404));
        }
        $this->photo_model = new photosPhotoModel();
        $photo = $this->photo_model->getByField('url', $url);

        if (!$photo) {
            throw new waException(_w('Page not found'), 404);
        }
        if (!$this->inCollection($photo, $this->hash)) {
            throw new waException(_w('Page not found'), 404);
        }
        $photo = photosPhoto::escapeFields($photo);

        $size = waRequest::get('size', null, waRequest::TYPE_STRING);

        $is_mini = waRequest::get('mini', 0, waRequest::TYPE_INT);
        if ($is_mini) {
            $size = $size ? $size : photosPhoto::getBigPhotoSize();
            // mini version of loading photo (in albums loading photo in stack)
            $photo['thumb_custom'] = photosPhoto::getThumbInfo($photo, $size);
            $this->response['photo'] = $photo;
            return;
        }

        // delegate work to special render helper
        $render_helper = new photosPhotoRenderHelper($photo);
        $result = $render_helper->workUp(array(
            'hash' => $this->hash,
            'need_photo_stream' => false
        ));
        if ($size) {
            $result['photo']['thumb_custom'] = photosPhoto::getThumbInfo($result['photo'], $size);
        }

        // pull out result of working up
        $this->response['photo']     = $result['photo'];
        $this->response['tags']      = $result['blocks']['tags'];
        $this->response['exif']      = $result['blocks']['exif'];
        $this->response['albums']    = $result['blocks']['albums'];
        $this->response['author']    = $result['blocks']['author'];
        $this->response['stack_nav'] = $result['blocks']['stack_nav'];

        /**
         * Add extra widgets to photo page
         * @event frontend_photo
         * @param string[array]mixed $photo photo data
         * @return array[string][string]string $return[%plugin_id%]['bottom'] In bottom, under photo - any widget
         */
        $this->response['frontend_photo'] = wa()->event('frontend_photo', $photo);
    }

    private function inCollection($photo, $hash)
    {
        $parent = $this->photo_model->getStackParent($photo);
        $photo = $parent ? $parent : $photo;
        // check existing in collection
        $collection = new photosCollection($hash);
        $current_offset = $collection->getPhotoOffset($photo);
        $collection_photos = $collection->getPhotos("id", $current_offset, 1, false);
        return isset($collection_photos[$photo['id']]);
    }
}