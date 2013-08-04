<form id="contactsLdap" action="#" method="post">
	<div id="contactsldap" class="personalblock">
	<ul>
		<li><?php p($l->t('LDAP Directories for Contacts'));?></li>
	</ul>
	<fieldset id="contactsLdap">
		<p><label for="ldap_addressbook_chooser"><?php p($l->t('Server configuration'));?></label>
		<select id="ldap_addressbook_chooser" name="ldap_addressbook_chooser">
		<?php 
		foreach($_['ldapArray'] as $ldapArray) {
		?>
			<option value="<?php p($ldapArray['uri']); ?>"><?php p($ldapArray['displayname']); ?></option>
		<?php
		}
		?>
		<option value="NEW"><?php p($l->t('Add LDAP addressbook'));?></option>
		</select>
		<button id="ldap_action_delete_configuration"
			name="ldap_action_delete_configuration"><?php p($l->t('Delete Addressbook'));?></button>
		</p>
		<input type="hidden" id="ldapid" name="ldapid" />
		<p><label for="displayname"><?php p($l->t('Addressbook name'));?></label>
		<input type="text" id="displayname" name="displayname" title="<?php p($l->t('Name of the addressbook'));?>"></p>
			
		<p><label for="description"><?php p($l->t('Addressbook description'));?></label>
		<input type="text" id="description" name="description" /></p>
			
		<p><label for="uri"><?php p($l->t('Addressbook uri'));?></label>
		<input type="text" id="uri" name="uri"
			title="<?php p($l->t('Must be unique'));?>"></p>
			
		<p><label for="ldapurl"><?php p($l->t('LDAP Server'));?></label>
		<input type="text" id="ldapurl" name="ldapurl"
			title="<?php p($l->t('URL of the LDAP server (ldap:// or ldaps://)'));?>" /></p>

		<p><label for="ldapbasednsearch"><?php p($l->t('Base DN for search'));?></label>
		<input type="text" id="ldapbasednsearch" name="ldapbasednsearch"
			title="<?php p($l->t('Search is recursive'));?>" /></p>

		<p><label for="ldapreadonly"><?php p($l->t('Read only'));?></label>
		<input type="checkbox" id="ldapreadonly" name="ldapreadonly"
			title="<?php p($l->t(''));?>" /></p>

		<p><label for="ldapbasednmodify"><?php p($l->t('Base DN for modification'));?></label>
		<input type="text" id="ldapbasednmodify" name="ldapbasednmodify"
			title="<?php p($l->t('All new contacts will be saved in this directory'));?>" /></p>

		<p><label for="ldapanonymous"><?php p($l->t('Anonymous'));?></label>
		<input type="checkbox" id="ldapanonymous" name="ldapanonymous"
			title="<?php p($l->t(''));?>" /></p>

		<p><label for="ldapuser"><?php p($l->t('User'));?></label>
		<input type="text" id="ldapuser" name="ldapuser"
			title="<?php p($l->t(''));?>" /></p>

		<p><label for="ldappass"><?php p($l->t('Password'));?></label>
		<input type="password" id="ldappass" name="ldappass"
			title="<?php p($l->t(''));?>" /></p>

		<p><label for="ldap_vcard_connector"><?php p($l->t('LDAP to VCard connector XML'));?></label>
		<textarea id="ldap_vcard_connector" name="ldap_vcard_connector"></textarea></p>

	</fieldset>
	<input id="ldap_submit" type="submit" value="Save" /> <button id="ldap_action_test_connection" name="ldap_action_test_connection"><?php p($l->t('Test connection'));?></button> <a href="#" target="_blank"><img src="<?php print_unescaped(OCP\Util::imagePath('', 'actions/info.png')); ?>" style="height:1.75ex" /> <?php p($l->t('Help'));?></a>
	</div>

</form>
