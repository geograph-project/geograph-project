== {literal}{{int:filedesc}}{/literal} ==
{literal}{{{/literal}Information
|Description={literal}{{{/literal}en|1={$image->title|escape:'html'}{literal}}}{/literal}
|Source=From [{$self_host}/photo/{$image->gridimage_id} geograph.org.uk]
{if $image->imagetaken && strpos($image->imagetaken,'0000') !== 0}
|Date={$image->imagetaken|replace:'-00':''}
{else}
|Date={$image->submitted|date_format:'%Y-%m-%dT%H:%M:%S+00:00'}
{/if}
|Author=[{$self_host}{$image->profile_link} {$image->realname|escape:'html'}]
|Permission=Creative Commons Attribution Share-alike license 2.0
|Other fields={literal}{{{/literal}Credit line
 |Author={$image->realname|escape:'html'}
 |License=[https://creativecommons.org/licenses/by-sa/2.0/ CC BY-SA 2.0]
 |Other=''{$image->title|escape:'html'}''
{literal} }}
}}{/literal}
{if $photographer_lat}
{literal}{{{/literal}Location|{$photographer_lat|string_format:"%.6f"}|{$photographer_long|string_format:"%.6f"}|source:geograph-{if $image->grid_square->reference_index==1}osgb36{else}irishgrid{/if}({$image->getPhotographerGridref(false)}){if $image->view_direction > -1}_heading:{$image->view_direction}{/if}|prec={$image->photographer_gridref_precision}{literal}}}{/literal}
{/if}
{literal}{{{/literal}Object location|{$lat|string_format:"%.5f"}|{$long|string_format:"%.5f"}|source:geograph-{if $image->grid_square->reference_index==1}osgb36{else}irishgrid{/if}({$image->getSubjectGridref(false)}){if $image->view_direction > -1}_heading:{$image->view_direction}{/if}|prec={$image->subject_gridref_precision}{literal}}}{/literal}

== {literal}{{int:license-header}}{/literal} ==
{literal}{{{/literal}geograph|{$image->gridimage_id}|{$image->realname|escape:'html'}{literal}}}{/literal}
