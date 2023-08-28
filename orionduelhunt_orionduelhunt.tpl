{OVERALL_GAME_HEADER}
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" class="svg_hexagon" style="display: none;">
    <path id="hexopath" d="M26 2, 75 2, 98 43, 75 84.6, 25 84.6, 2 43 z">
    </path>
    <clipPath id="hexoclip">
        <use href="#hexopath" fill="none" stroke="currentColor" clip-path="url(#hexoclip)" </use>
    </clipPath>
    <use id="hexo" class="hexo" href="#hexopath" fill="none" stroke="currentColor" clip-path="url(#hexoclip)">
    </use>
    <use class="hexo_filled" href="#hexopath" stroke-width="1" fill="currentColor">
    </use>
</svg>
<div id="resized_id">
    <div id="play_area_id" class="play_area">
       <div id="player_board_id" class="board"></div>
    </div>
</div>


<script type="text/javascript">
    const jstpl_hex = '<div id="hex_${id}" class="hex ${class}" style="top: ${top}px; left: ${left}px;"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" class="hexagon" viewBox="2 2 96 82.6"><use href="#hexo"></use></svg></div>';
    const jstpl_elt_black_hole = '<div id="black_hole_${id}" class="element black_hole" style="top: ${top}px; left: ${left}px;"></div>';
    const jstpl_elt_galaxy = '<div id="galaxy_${id}" class="element galaxy" style="top: ${top}px; left: ${left}px;"></div>';


</script>

{OVERALL_GAME_FOOTER}
