<?php

// The free base defines its constants before deciding whether to boot, so the constants alone
// do not prove there is a base to build on.
function pmuip_base_is_active() {
	return defined('PMUI_EDITION') && 'free' === PMUI_EDITION && class_exists('PMUI_Plugin', false);
}
