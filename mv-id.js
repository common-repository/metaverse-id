mv_id_plugin = {
	add_more   : function()
	{
		var li = document.createElement('li');
		var select = document.createElement('select');
		select.name = 'add[' + mv_id_plugin__num_entries + '][metaverse]';
		for(i in mv_id_plugin.metaverses)
		{
			var option = document.createElement('option');
			option.value = mv_id_plugin.metaverses[i];
			option.title = mv_id_plugin.formats[i];
			option.appendChild(document.createTextNode(mv_id_plugin.nice_names[mv_id_plugin.metaverses[i]]));
			select.appendChild(option);
			select.appendChild(document.createTextNode("\n"));
		}
		var input = document.createElement('input');
		input.type = 'text';
		input.maxLength = 255;
		input.name = 'add[' + (mv_id_plugin__num_entries++) + '][id]';
		li.appendChild(select);
		li.appendChild(input);
		var ol = mv_id_plugin__id_div.getElementsByTagName('ol')[0];
		ol.appendChild(li);
	},
	populate_select_mv : function(mv_element,ids_element,instance)
	{
		var select = jQuery('#' + mv_element);
		jQuery(select).empty();
		for(var i in mv_id_plugin.ids){
			var option = jQuery('<option>' + jQuery('<option>' + mv_id_plugin.nice_names[i] + '<\/option>').text() + '<\/option>');
			option.attr('value', i);
			option.attr('selected', (typeof instance != 'undefined' && instance.metaverse == i));
			select.append(option[0]);
		}
		mv_id_plugin.populate_select_id(mv_element,ids_element);
		select.change(function(){mv_id_plugin.populate_select_id(mv_element,ids_element,instance)});
		select.click(function(){mv_id_plugin.populate_select_id(mv_element,ids_element,instance)});
	},
	populate_select_id : function(mv_element,ids_element,instance)
	{
		var _select = jQuery('#' + mv_element);
		var select = jQuery('#' + ids_element);
		_select.children('option').each(function(){
			if(jQuery(this).attr('selected')){
				select.empty();
				for(var x in mv_id_plugin.ids[jQuery(this).attr('value')]){
					var value = mv_id_plugin.ids[jQuery(this).attr('value')][x].id;
					var option = jQuery('<option>' + jQuery('<option>' + value + '<\/option>').text() + '<\/option>');
					option.attr('value',value);
					option.attr('selected', (typeof instance != 'undefined' && instance.id == value));
					select.append(option[0]);
				}
			}
		});
	}
};