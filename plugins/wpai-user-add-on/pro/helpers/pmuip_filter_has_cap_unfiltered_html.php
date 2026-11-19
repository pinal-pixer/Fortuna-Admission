<?php

function pmuip_filter_has_cap_unfiltered_html($caps) {
	$caps['unfiltered_html'] = true;
	return $caps;
}
