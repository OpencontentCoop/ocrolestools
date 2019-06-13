<h1 class="u-text-h2">Importa il ruolo {$remote.name|wash()} da {$remote_url}</h1>

{if and(is_set($locale), $locale)}
    <div class="message-error u-margin-bottom-l">
        <strong>Attenzione:</strong> Il ruolo <a href="{concat('role/view/',$locale.id)|ezurl(no)}">{$locale.name} è già
            presente in questa installazione</a>.<br/>
        Cliccando sul bottone "Importa", tutte le policy del ruolo verranno sovrascritte
    </div>
{/if}

{if count($errors)|gt(0)}
    <div class="message-error">
        <strong>Attenzione i seguenti errori impediscono la sincronizzazione</strong>
        {foreach $errors as $error}
            <p>{$error|wash()}</p>
        {/foreach}
    </div>
{/if}

{if is_set($data)}
<form class="clearfix u-cf u-margin-bottom-l" method="post" action="{concat('rolestools/import/', $remote.name, '?remote=', $remote_url)|ezurl(no)}">
    {foreach $data.policies as $index => $policy}
    <table class="table list Table Table--withBorder Table--compact u-margin-bottom-xxl u-border-all-xs u-text-r-xxs">
        <tr>
            <th>Modulo</th>
            <th>Funzione</th>
            <th>Nome limitazione</th>
            <th>Valore limitazione</th>
            <th>Valori remoti</th>
        </tr>

            {if count($policy.Limitation)|eq(0)}
                <tr>
                    <td>{$policy.ModuleName}</td>
                    <td>{$policy.FunctionName}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            {else}
                {foreach $policy.Limitation as $name => $value}
                    <tr class="{if is_set($errors[$value])}alert alert-danger{elseif and( is_array($value)|not(), is_set($fixes[$value]) )}alert alert-warning{/if}">
                        <td>{$policy.ModuleName}</td>
                        <td>{$policy.FunctionName}</td>
                        <td>{$name}</td>
                        <td>
                            {if is_set($fixes[$value])}

                                {if is_set($fix_errors[$value])}
                                    <strong>{$fix_errors[$value]|wash()}</strong>
                                {/if}
                                {if $fixes[$value].name|eq('Subtree')}
                                    <input type="text" class="form-control Form-field" value="{$fixes_data[$value]}"
                                           placeholder="Id di nodo separati da virgola" name="{$value}"/>
                                {elseif $fixes[$value].name|eq('Node')}
                                    <input type="text" class="form-control Form-field" value="{$fixes_data[$value]}" placeholder="Id di nodo"
                                           name="{$value}"/>
                                {elseif or($fixes[$value].name|eq('Class'), $fixes[$value].name|eq('ParentClass'))}
                                    <input type="text" class="form-control Form-field" value="{$fixes_data[$value]}"
                                           placeholder="Identificatori di classe separati da virgola" name="{$value}"/>
                                {elseif $fixes[$value].name|eq('Section')}
                                    <input type="text" class="form-control Form-field" value="{$fixes_data[$value]}"
                                           placeholder="Identificatori di sezione separati da virgola" name="{$value}"/>
                                {/if}

                            {else}

                                {def $current_value = $value}
                                {if is_array($current_value)|not()}
                                    {set $current_value = array($current_value)}
                                {/if}

                                {foreach $value as $element}
                                    {if or($name|eq('Subtree'),$name|eq('Node'))}
                                        {def $node_id = $element|explode('/')|extract_right(2)|implode('')}
                                        {fetch( 'content', 'node', hash(node_id, $node_id)).name|wash()} ({$node_id})
                                        {undef $node_id}
                                    {elseif or($name|eq('Class'), $name|eq('ParentClass'))}
                                        {fetch( 'content', 'class', hash( 'class_id', $element ) ).identifier|wash()} ({$element})
                                    {elseif $name|eq('Section')}
                                        {$sections[$element]} ({$element})
                                    {else}
                                        {$element}
                                    {/if}
                                    {delimiter}<br>{/delimiter}
                                {/foreach}


                                {def $fix_data_key = concat('FIX-',$index,'/',$policy.ModuleName,'/',$policy.FunctionName,'/',$name)}
                                {if is_set($fixes_data[$fix_data_key])}
                                    <input type="hidden" value="{$fixes_data[$fix_data_key]}" name="{$fix_data_key}"/>
                                {/if}
                                {undef $fix_data_key}
                                {undef $current_value}
                            {/if}
                        </td>
                        <td>
                            {foreach $remote.policies[$index]['Limitation'][$name] as $item}
                                {if or($name|eq('Subtree'),$name|eq('Node'))}
                                    {$item.name|wash()} ({$item.node_id|wash()})
                                {else}
                                    {$item}
                                {/if}
                                {delimiter}<br>{/delimiter}
                            {/foreach}
                        </td>
                    </tr>
                {/foreach}
            {/if}
    </table>
    {/foreach}
    <div>
        <input class="btn btn-lg btn-success defaultbutton pull-right" type="submit" name="ImportRole" value="Importa"/>
    </div>
</form>
{/if}