<div class="mt-3">
    <a class="small text-decoration-none" href="home">&larr; {$L.back}</a>

    <div class="card mt-2 mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <div class="text-muted small">{$p.reference|escape:'html'}</div>
                    <h2 class="mb-1">{$p.address|escape:'html'}</h2>
                    <div class="text-muted">{$p.city|escape:'html'}{if $p.city} &middot; {/if}{$p.type_label|escape:'html'}</div>
                </div>
                <div class="text-end">
                    <span class="badge text-bg-{$p.badge} fs-6">{$p.status_label|escape:'html'}</span>
                    {if $p.price_text}<div class="fs-4 fw-semibold mt-1">{$p.price_text|escape:'html'}</div>{/if}
                </div>
            </div>
            {if $p.owner_name}<p class="mt-3 mb-0"><strong>{$L.owner_name}:</strong> {$p.owner_name|escape:'html'}</p>{/if}
            {if $p.description}<p class="mt-2 mb-0" style="white-space:pre-line">{$p.description|escape:'html'}</p>{/if}
        </div>
        {if $is_admin or $can_add_doc}
        <div class="card-footer d-flex gap-2">
            {if $can_add_doc}<a class="btn btn-primary btn-sm" href="add?property_id={$p.id}">+ {$L.upload_here}</a>{/if}
            {if $is_admin}<a class="btn btn-outline-secondary btn-sm" href="admin_properties?action=edit&amp;id={$p.id}">{$L.edit}</a>{/if}
        </div>
        {/if}
    </div>

    <h4 class="mb-3">{$L.documents} <span class="text-muted fs-6">({$doc_total})</span></h4>

    {if $groups}
        {foreach from=$groups item=g}
        <div class="card mb-3">
            <div class="card-header">{$g.name|escape:'html'}</div>
            <ul class="list-group list-group-flush">
                {foreach from=$g.docs item=d}
                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        {if $d.view_link}<a href="{$d.view_link|escape:'html'}">{$d.realname|escape:'html'}</a>{else}{$d.realname|escape:'html'}{/if}
                        {if $d.description}<div class="small text-muted">{$d.description|escape:'html'}</div>{/if}
                    </div>
                    <div class="small text-muted text-nowrap">
                        {$d.created_text|escape:'html'}
                        &middot; <a href="{$d.details_link|escape:'html'}">{$L.details}</a>
                    </div>
                </li>
                {/foreach}
            </ul>
        </div>
        {/foreach}
    {else}
        <div class="alert alert-light border">{$L.no_documents}</div>
    {/if}
</div>
