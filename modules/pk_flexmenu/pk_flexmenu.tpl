<div class="flexmenu-container">	
	<div class="page_width">
		<div class="flexmenu">
			<div class="mobileMenuTitle lmromandemi">{l s='Menu' mod='pk_flexmenu'}</div>
			<ul class="flexmenu_ul">
				<li class="menu_logo">
					<a href="{$base_dir}" title="{$shop_name|escape:'html':'UTF-8'}">
						<img src="{$logo_url}" alt="{$shop_name|escape:'htmlall':'UTF-8'}" {if isset($theme_settings.logo_type) && $theme_settings.logo_type == 1}hidden{/if} />
						<span id="logo-text-menu" class="{if isset($theme_settings.logo_type) && $theme_settings.logo_type == 0}hidden{/if}">
								{if isset($theme_settings.logo_text)}<span class="logo main_color">{$theme_settings.logo_text}</span>{/if}
						</span>	
					</a>
				</li>
				{$flexmenu}
			</ul>
		</div>
	</div>
</div>