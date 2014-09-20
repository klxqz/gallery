<?php

$plugin_id = array('photos', 'gallery');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'status', '1');
$app_settings_model->set($plugin_id, 'assign_tag', '');
$app_settings_model->set($plugin_id, 'need_moderation', '1');
$app_settings_model->set($plugin_id, 'self_vote', '1');
$app_settings_model->set($plugin_id, 'min_size', '1');
$app_settings_model->set($plugin_id, 'max_size', '5000');
$app_settings_model->set($plugin_id, 'albums', '[]');
$app_settings_model->set($plugin_id, 'subalbums', '[]');


$model = new waModel();
try {
    $model->query("SELECT moderation FROM `photos_photo` WHERE 0");
} catch (waException $e) {
    // 0 - waited
    // 1 - approved
    // -1 - declined
    $sql = "ALTER TABLE `photos_photo` ADD COLUMN moderation TINYINT(1) NOT NULL DEFAULT 1";
    $model->query($sql);
}

try {
    $model->query("SELECT `votes_count` FROM `photos_photo` WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE `photos_photo` ADD COLUMN votes_count INT(11) NOT NULL DEFAULT 0");
}

$contact_id = wa()->getUser()->getId();
$photo_model = new photosPhotoModel();

$data = array();
foreach ($photo_model->select('id, rate')->where('rate > 0')->fetchAll() as $item) {
    $data[] = array(
        'photo_id' => $item['id'],
        'contact_id' => $contact_id,
        'rate' => $item['rate'],
        'datetime' => date('Y-m-d H:i:s'),
        'ip' => waRequest::getIp(true)
    );
}

$vote_model = new photosGalleryVoteModel();
$vote_model->multipleInsert($data);

$model->exec("UPDATE `photos_photo` SET votes_count = 1 WHERE rate > 0");
