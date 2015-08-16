<script type="text/html" id="tmpl-rpbt-progress-barr">
<style type='text/css'>
	#rpbt_cache_progressbar {
        width: 400px;
        height: 22px;
        border: 1px solid #111;
        background-color: #292929;
        position: relative;
        -webkit-border-radius: 25px;
        -moz-border-radius: 25px;
        border-radius: 25px;
        overflow: hidden;
    }
    #rpbt_cache_progressbar div {
        height: 100%;
        color: #fff;
        line-height: 22px; /* same as #progressBar height if we want text middle aligned */
        width: 0;
        background-color: #0099ff;

    }
    #rpbt_cache_progressbar span {
        position: absolute;
        width: 100%;
        text-align: center;
    }
    #rpbt_error {
    	color: red;
    }
</style>
<div id="rpbt_cache_progress_bar_container" aria-hidden="true">
	<p id="rpbt_error"></p>
	<p>
	{{data.count}} posts found.<br/>
	<strong>Please don't leave or refresh this page, and wait untill all {{data.cache_count}} posts are cached</strong>
	</p>
    <div id="rpbt_cache_progressbar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"><div><span></span></div></div>
    <p id="rpbt_notice"></p>
    <p><a href="{{data.settings_page}}">Back to plugin page</a></p>
    <a href="#" class="rpbt_cache_parameter_toggle" aria-controls="rpbt_parameters" aria-expanded="false" >{{data.show}}</a>
    <div id="rpbt_parameters" style="display:none;" aria-hidden="true">{{{data.parameters}}}</div>
</div>
</script>