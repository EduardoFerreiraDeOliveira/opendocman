<div class="mt-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0">{$L.properties}</h2>
        <div class="d-flex gap-2">
            {if $is_admin}<a class="btn btn-primary btn-sm" href="admin_properties?action=new">+ {$L.add}</a>{/if}
            <a class="btn btn-outline-secondary btn-sm" href="out">{$L.all_documents}</a>
        </div>
    </div>

    <form method="get" action="home" class="row g-2 mb-4">
        <div class="col-lg-5">
            <input type="search" name="q" class="form-control" placeholder="{$L.search|escape:'html'}" value="{$f.search|escape:'html'}">
        </div>
        <div class="col-6 col-lg-2">
            <select name="status" class="form-select">
                <option value="">{$L.status}: {$L.all}</option>
                {foreach from=$statuses item=st}
                <option value="{$st.code}" {if $f.status eq $st.code}selected{/if}>{$st.label|escape:'html'}</option>
                {/foreach}
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <select name="type" class="form-select">
                <option value="">{$L.type}: {$L.all}</option>
                {foreach from=$types item=t}
                <option value="{$t.code}" {if $f.type eq $t.code}selected{/if}>{$t.label|escape:'html'}</option>
                {/foreach}
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <select name="city" class="form-select">
                <option value="">{$L.city}: {$L.all}</option>
                {foreach from=$cities item=c}
                <option value="{$c|escape:'html'}" {if $f.city eq $c}selected{/if}>{$c|escape:'html'}</option>
                {/foreach}
            </select>
        </div>
        <div class="col-6 col-lg-1 d-grid">
            <button type="submit" class="btn btn-primary">{$L.filter}</button>
        </div>
    </form>

    {if $properties}
    <div class="row g-3">
        {foreach from=$properties item=p}
        <div class="col-12 col-md-6 col-xl-4">
            <a class="text-decoration-none text-reset" href="property?id={$p.id}">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-muted small">{$p.reference|escape:'html'}</span>
                            <span class="badge text-bg-{$p.badge}">{$p.status_label|escape:'html'}</span>
                        </div>
                        <h5 class="card-title mb-1">{$p.address|escape:'html'}</h5>
                        <p class="text-muted mb-2">{$p.city|escape:'html'}{if $p.city} &middot; {/if}{$p.type_label|escape:'html'}</p>
                        {if $p.price_text}<p class="fs-5 fw-semibold mb-2">{$p.price_text|escape:'html'}</p>{/if}
                    </div>
                    <div class="card-footer d-flex justify-content-between small text-muted">
                        <span>{$p.doc_count|escape:'html'} {$L.documents|lower}</span>
                        {if $p.last_document_text}<span>{$L.last_document}: {$p.last_document_text|escape:'html'}</span>{/if}
                    </div>
                </div>
            </a>
        </div>
        {/foreach}
    </div>
    {else}
    <div class="alert alert-light border">{$L.no_results}</div>
    {/if}
</div>
