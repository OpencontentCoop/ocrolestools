<h1 class="u-text-h2">Importa il ruolo {$remote.name|wash()} da {$remote_url}</h1>

{if $locale}
    <div class="message-error">
        <strong>Attenzione:</strong> Il ruolo <a href="{concat('role/view/',$locale.id)|ezurl(no)}">{$locale.name} è già
            presente in questa installazione</a>.<br/>
        Cliccando sul bottone "Importa", tutte le policy del ruolo verranno sovrascritte
    </div>
{/if}

{if count($errors)|gt(0)}
    <div class="message-warning">
        <strong>Attenzione i seguenti errori impediscono la sincronizzazione</strong>
        {foreach $errors as $error}
            <p>{$error|wash()}</p>
        {/foreach}
    </div>
{/if}

<form method="post" action="{concat('rolestools/import/', $remote.name, '?remote=', $remote_url)|ezurl(no)}">
    <table class="table list">
        <tr>
            <th>Modulo</th>
            <th>Funzione</th>
            <th>Nome limitazione</th>
            <th>Valore limitazione</th>
            <th>Valori remoti</th>
        </tr>
        {foreach $data.policies as $index => $policy}
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
                    <tr{if and( is_array($value)|not(), is_set($fixes[$value]) )} class="warning"{/if}>
                        <td>{$policy.ModuleName}</td>
                        <td>{$policy.FunctionName}</td>
                        <td>{$name}</td>
                        <td>

                            {if is_set($fixes[$value])}
                                {if $fixes[$value].name|eq('Subtree')}
                                    <p>
                                        <small><strong>Valori della policy remota: </strong><br/>
                                            {foreach $fixes[$value].values as $item}
                                                {$item.name|wash()} ({$item.node_id|wash()}),
                                            {/foreach}
                                        </small>
                                    </p>
                                    <input type="text" value="{$fixes_data[$value]}"
                                           placeholder="Id di nodo separati da virgola" name="{$value}"/>
                                {elseif $fixes[$value].name|eq('Node')}
                                    <p>
                                        <small><strong>Valori della policy remota: </strong><br/>
                                            {foreach $fixes[$value].values as $item}
                                                {$item.name|wash()} ({$item.node_id|wash()}),
                                            {/foreach}
                                        </small>
                                    </p>
                                    <input type="text" value="{$fixes_data[$value]}" placeholder="Id di nodo"
                                           name="{$value}"/>
                                {elseif or($fixes[$value].name|eq('Class'), $fixes[$value].name|eq('ParentClass'))}
                                    <p>
                                        <small><strong>Valori della policy remota: </strong><br/>
                                            {$fixes[$value].values|implode(',')},
                                        </small>
                                    </p>
                                    <input type="text" value="{$fixes_data[$value]}"
                                           placeholder="Id di classe separati da virgola" name="{$value}"/>
                                {/if}
                            {else}
                                {if is_array($value)}
                                    <ul class="list-unstyled">
                                        {foreach $value as $item}
                                            <li>{$item}</li>
                                        {/foreach}
                                    </ul>
                                {else}
                                    {$value}
                                {/if}
                                {def $fix_data_key = concat('FIX-',$index,'/',$policy.ModuleName,'/',$policy.FunctionName,'/',$name)}
                                {if is_set($fixes_data[$fix_data_key])}
                                    <input type="hidden" value="{$fixes_data[$fix_data_key]}" name="{$fix_data_key}"/>
                                {/if}
                                {undef $fix_data_key}

                            {/if}
                        </td>
                        <td>
                            {foreach $remote.policies[$index]['Limitation'][$name] as $item}
                                {if or($name|eq('Subtree'),$name|eq('Node'))}
                                    {$item.name|wash()} ({$item.node_id|wash()})
                                    <br/>
                                {else}
                                    {$item}
                                    <br/>
                                {/if}
                            {/foreach}
                        </td>
                    </tr>
                {/foreach}
            {/if}
        {/foreach}
    </table>
    <div>
        <input class="btn btn-lg btn-success defaultbutton pull-right" type="submit" name="ImportRole" value="Importa"/>
    </div>
</form>