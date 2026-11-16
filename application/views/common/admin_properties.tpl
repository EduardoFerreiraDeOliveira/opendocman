{if $mode eq 'form'}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{if $p.id}{$L.edit}{else}{$L.add}{/if}</h3>
    <a class="btn btn-outline-secondary btn-sm" href="admin_properties">{$L.cancel}</a>
</div>

<form method="post" action="admin_properties" class="needs-validation">
    {$csrf_token_field}
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="{$p.id|escape:'html'}">

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="prop_reference">{$L.reference} *</label>
            <input type="text" id="prop_reference" name="reference" maxlength="50" required
                   class="form-control {if $form_error.reference}is-invalid{/if}" value="{$p.reference|escape:'html'}">
            {if $form_error.reference}<div class="invalid-feedback d-block">{$form_error.reference}</div>{/if}
        </div>
        <div class="col-md-8">
            <label class="form-label" for="prop_address">{$L.address} *</label>
            <input type="text" id="prop_address" name="address" maxlength="255" required
                   class="form-control {if $form_error.address}is-invalid{/if}" value="{$p.address|escape:'html'}">
            {if $form_error.address}<div class="invalid-feedback d-block">{$form_error.address}</div>{/if}
        </div>
        <div class="col-md-4">
            <label class="form-label" for="prop_city">{$L.city}</label>
            <input type="text" id="prop_city" name="city" maxlength="100" class="form-control" value="{$p.city|escape:'html'}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="prop_type">{$L.type}</label>
            <select id="prop_type" name="type" class="form-select">
                {foreach from=$types item=t}
                <option value="{$t.code}" {if $p.type eq $t.code}selected{/if}>{$t.label|escape:'html'}</option>
                {/foreach}
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="prop_status">{$L.status}</label>
            <select id="prop_status" name="status" class="form-select">
                {foreach from=$statuses item=st}
                <option value="{$st.code}" {if $p.status eq $st.code}selected{/if}>{$st.label|escape:'html'}</option>
                {/foreach}
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="prop_price">{$L.price} (&euro;)</label>
            <input type="text" id="prop_price" name="price" inputmode="decimal"
                   class="form-control {if $form_error.price}is-invalid{/if}" value="{$p.price|escape:'html'}">
            {if $form_error.price}<div class="invalid-feedback d-block">{$form_error.price}</div>{/if}
        </div>
        <div class="col-md-8">
            <label class="form-label" for="prop_owner">{$L.owner_name}</label>
            <input type="text" id="prop_owner" name="owner_name" maxlength="255" class="form-control" value="{$p.owner_name|escape:'html'}">
        </div>
        <div class="col-12">
            <label class="form-label" for="prop_description">{$L.description}</label>
            <textarea id="prop_description" name="description" rows="4" class="form-control">{$p.description|escape:'html'}</textarea>
        </div>
    </div>

    <hr class="my-4">
    <h5>{$L.permissions}</h5>
    <p class="text-muted small">{$L.permissions_help}</p>

    <div class="row g-4">
        <div class="col-md-6">
            <h6>{$L.departments}</h6>
            <table class="table table-sm align-middle">
                <tbody>
                {foreach from=$departments item=d}
                <tr>
                    <td>{$d.name|escape:'html'}</td>
                    <td style="width:55%">
                        <select name="perm_dept[{$d.id}]" class="form-select form-select-sm">
                            {foreach from=$rights_options item=o}
                            <option value="{$o.value}" {if $d.rights eq $o.value}selected{/if}>{$o.label}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <h6>{$L.users}</h6>
            <table class="table table-sm align-middle">
                <tbody>
                {foreach from=$users item=u}
                <tr>
                    <td>{$u.name|escape:'html'}</td>
                    <td style="width:55%">
                        <select name="perm_user[{$u.id}]" class="form-select form-select-sm">
                            {foreach from=$rights_options item=o}
                            <option value="{$o.value}" {if $u.rights eq $o.value}selected{/if}>{$o.label}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">{$L.save}</button>
        <a class="btn btn-outline-secondary" href="admin_properties">{$L.cancel}</a>
    </div>
</form>
{else}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{$L.properties}</h3>
    <a class="btn btn-primary btn-sm" href="admin_properties?action=new">+ {$L.add}</a>
</div>

{if $properties}
<div class="table-responsive">
<table class="table table-hover align-middle">
    <thead>
        <tr>
            <th>{$L.reference}</th>
            <th>{$L.address}</th>
            <th>{$L.city}</th>
            <th>{$L.type}</th>
            <th>{$L.status}</th>
            <th class="text-end">{$L.price}</th>
            <th class="text-end">{$L.documents}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$properties item=row}
        <tr>
            <td><a href="property?id={$row.id}">{$row.reference|escape:'html'}</a></td>
            <td>{$row.address|escape:'html'}</td>
            <td>{$row.city|escape:'html'}</td>
            <td>{$row.type_label|escape:'html'}</td>
            <td>{$row.status_label|escape:'html'}</td>
            <td class="text-end">{$row.price_text|escape:'html'}</td>
            <td class="text-end">{$row.doc_count|escape:'html'}</td>
            <td class="text-end text-nowrap">
                <a class="btn btn-outline-primary btn-sm" href="admin_properties?action=edit&amp;id={$row.id}">{$L.edit}</a>
                <form method="post" action="admin_properties" class="d-inline"
                      onsubmit="return confirm(this.getAttribute('data-confirm'));"
                      data-confirm="{$L.confirm_delete|escape:'html'}">
                    {$csrf_token_field}
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="{$row.id}">
                    <button type="submit" class="btn btn-outline-danger btn-sm">{$L.delete}</button>
                </form>
            </td>
        </tr>
    {/foreach}
    </tbody>
</table>
</div>
{else}
<p class="text-muted">{$L.no_results}</p>
{/if}
{/if}
