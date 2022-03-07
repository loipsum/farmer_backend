<?php

use App\Models\Entry;

if (!function_exists('getErrorMessages')) {
    function getErrorMessages($messageArrays)
    {
        $errorMessages = [];
        foreach ($messageArrays as $messageArrayItems) {
            foreach ($messageArrayItems as $message) {
                array_push($errorMessages, $message);
            }
        }
        return $errorMessages;
    }
}

if (!function_exists('delete_photo_thumbnail')) {
    function delete_photo_thumbnail(Entry $entry)
    {
        $to_delete = [
            $entry->photo ? substr($entry->photo->url, 30) : '',
            $entry->thumbnail ? substr($entry->thumbnail->url, 30) : ''
        ];
        \Storage::delete($to_delete);
        $entry->photo->delete();
        $entry->thumbnail->delete();
    }
}
