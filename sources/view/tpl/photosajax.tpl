{{foreach $photos as $photo}}
{{include file="photo_top.tpl"}}
{{/foreach}}
<script>justifyPhotosAjax('photo-album-contents-{{$album_id}}')</script>
