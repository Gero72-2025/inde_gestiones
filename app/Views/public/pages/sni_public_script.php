<link rel="stylesheet" href="https://cesium.com/downloads/cesiumjs/releases/1.124/Build/Cesium/Widgets/widgets.css">
<script src="https://cesium.com/downloads/cesiumjs/releases/1.124/Build/Cesium/Cesium.js"></script>
<script>
    window.PortalConfig.sni = {
        mode2d: <?= json_encode(lang('Portal.sniMode2D')) ?>,
        mode3d: <?= json_encode(lang('Portal.sniMode3D')) ?>,
        noData: <?= json_encode(lang('Portal.sniNoData')) ?>,
        loadError: <?= json_encode(lang('Portal.sniLoadError')) ?>,
        line69: <?= json_encode(lang('Portal.sniLine69')) ?>,
        line138: <?= json_encode(lang('Portal.sniLine138')) ?>,
        line230: <?= json_encode(lang('Portal.sniLine230')) ?>,
        substations: <?= json_encode(lang('Portal.sniSubstations')) ?>
    };
</script>
<script src="<?= esc(base_url('assets/js/sni/sni-public.js')) ?>"></script>
