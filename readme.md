# Websn PHP Bootstrap

Each directory name in the direcory of the `worker.php` file will be compared to the path segment after the bootstrap segment.

If a file in the directory and all sub directory matches it's content will be appened to the http response.

__Exceptions:__
+ The file is a dot file (example: `.somename.js`)
+ The file starts with an underscore (example: `_metadata.ini`)
+ Files not matching the `extension_filter` set in the [configuration](#metadata-file)

## Flags

|Name|Description|
|----|-----------|
|RNAME|Shows the file path relative from the `worker.php` file to the source file. <br/> This behavior can be disabled by setting the global `BOOTSTRAP_DISABLE_RELATIVE_NAME = true`.|

## Metadata File
The `_metadata.ini` in each directory can be used to specify information about the content.

__Options:__
|Name|Value-Type|Description|
|----|----------|-----------|
|`mimetype`|String|Used to tell the client what mimetype the content is.<br/>*Recommended so that the client dosen't need to determin for them self.*|
|`comment_prefix`|String|What needs to be used to start a Comment in the content.<br/>*Required to RNAME flag.*|
|`comment_suffix`|String|What needs to be used to end a Comment in the content. (Can be set to an empty string)<br/>*Required to RNAME flag.*|
|`extension_filter`|String|The Extension that a file in the directories must have to be included in the output.|
|`support_comment`|Boolean|Whether the content supports comments.|


