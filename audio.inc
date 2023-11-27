<?php

// code for click-to-play audio

// output an audio element with given ID and URL
//
function audio_element($id, $url) {
    echo sprintf('<audio id=a%d><source src="%s"></source></audio>',
        $id, $url
    );
}

// note icon that plays audio when clicked
//
function audio_listen_link($id) {
    $js = sprintf(
        ' onclick="play_sound(\'a%d\');" onmouseleave="stop_sound(\'a%d\');" ',
        $id, $id
    );
    return sprintf('&nbsp;<a title="Play sample" %s><img height=24px src=note.png></a>',
        $js
    );
}

// output Javascript for audio
//
function audio_enable() {
echo '
<script language="javascript" type="text/javascript">
function play_sound(id) {
    var audio = document.getElementById(id);
    audio.currentTime = 0;
    audio.play();
}

function stop_sound(id) {
    var audio = document.getElementById(id);
    audio.pause();
}
</script>
';
}

?>
