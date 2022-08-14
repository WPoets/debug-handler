<?php
namespace aw2\live_debug;


/*
	if LIVE_DEBUG===yes then live_debug.activate
*/


//define('LIVE_DEBUG', 'yes');

function event_raise($atts=null,$content=null,$shortcode=null){}



\aw2_library::add_service('live_debug.activate','Setup Debugging Env',['namespace'=>__NAMESPACE__]);
function activate($atts=null,$content=null,$shortcode=null){
	\aw2_library::set('@live_debug.active','yes');
}

\aw2_library::add_service('live_debug.deactivate','Setup Debugging Env',['namespace'=>__NAMESPACE__]);
function deactivate($atts=null,$content=null,$shortcode=null){
	\aw2_library::set('@live_debug.active','no');
}

\aw2_library::add_service('live_debug.active','Setup Debugging Env',['namespace'=>__NAMESPACE__]);
function active($atts=null,$content=null,$shortcode=null){
	return \aw2_library::get('@live_debug.active');
}


\aw2_library::add_service('live_debug.is_active','Setup Debugging Env',['namespace'=>__NAMESPACE__]);
function is_active($atts=null,$content=null,$shortcode=null){
	if(\aw2_library::get('@live_debug.active')==="yes") return true;
	return false;
}



\aw2_library::add_service('live_debug.setup.cookie','Setup Debugging Env',['func'=>'setup_cookie','namespace'=>__NAMESPACE__]);
function setup_cookie($atts=null,$content=null,$shortcode=null){
	
	if(IS_WP && is_admin()) return;
	
	if(isset($_COOKIE['live_debug'])){
		$ticket_id = $_COOKIE['live_debug'];
		$ticket= \aw2\session_ticket\get(["main"=>$ticket_id]);
		$content = $ticket['debug_code'];
	}
	
	setup($atts,$content);
}


\aw2_library::add_service('live_debug.setup','Setup Debugging Env',['namespace'=>__NAMESPACE__]);
function setup($atts=null,$content=null,$shortcode=null){

	\aw2_library::set('@live_debug',array(
		"active"=>"no",
		"event"=>array(),
		"output"=>array(),
		"is_publishing"=>"no",
		"left_events"=>"500"
	));
	\aw2_library::parse_shortcode($content);
	
	
}


\aw2_library::add_service('live_debug.start.add','Start when condition is met',['func'=>'start_add','namespace'=>__NAMESPACE__]);
function start_add($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;		
	
	extract(\aw2_library::shortcode_atts( array(
	'main'=>'',
	'event'=>null,
	), $atts, '' ) );
	
	//\util::var_dump("debug.start.add");
	//\util::var_dump($content);
	//\util::var_dump($atts);
	
	$ab=new \array_builder();
	$arr=$ab->parse($content);
	\aw2_library::set('@live_debug.start.conditions.new',$arr);
	
}

\aw2_library::add_service('live_debug.code.add','Stop Publishing Events',['func'=>'code_add','namespace'=>__NAMESPACE__]);
function code_add($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;		
	
	extract(\aw2_library::shortcode_atts( array(
	'main'=>'',
	'event'=>null,
	), $atts, 'dump' ) );
	
	\aw2_library::set('@live_debug.start.code.new',$content);
	
}


\aw2_library::add_service('live_debug.publish.start','Start Publishing Events',['func'=>'publish_start','namespace'=>__NAMESPACE__]);
function publish_start($atts=null,$content=null,$shortcode=null){
	
	$status = \aw2_library::get('@live_debug.is_publishing');
	if($status==='yes')return;	
	
	\aw2_library::set('@live_debug.is_publishing',"yes");
	
	include_once "lib/debug_icon.php";

}

\aw2_library::add_service('live_debug.publish.stop','Stop Publishing Events',['func'=>'publish_stop','namespace'=>__NAMESPACE__]);
function publish_stop($atts=null,$content=null,$shortcode=null){

	$status = \aw2_library::get('@live_debug.is_publishing');
	if($status==='no')return;	
	
	\aw2_library::set('@live_debug.is_publishing',"no");

}

\aw2_library::add_service('live_debug.publish.is_active','Check if publishing is active',['func'=>'publish_is_active','namespace'=>__NAMESPACE__]);
function publish_is_active($atts=null,$content=null,$shortcode=null){
	$status = \aw2_library::get('@live_debug.is_publishing');
	if($status==='yes')return true;	
	
	return false;
}

\aw2_library::add_service('live_debug.publish.active','gives the current status of is_publishing',['func'=>'publish_active','namespace'=>__NAMESPACE__]);
function publish_active($atts=null,$content=null,$shortcode=null){
	return \aw2_library::get('@live_debug.is_publishing');
}

\aw2_library::add_service('live_debug.publish.event','Publish Events',['func'=>'publish_event','namespace'=>__NAMESPACE__]);
function publish_event($atts=null,$content=null,$shortcode=null){
	if(!is_active()) return;		
	
	extract(\aw2_library::shortcode_atts( array(
	'event'=>'',
	'bgcolor'=>''
	), $atts, '' ) );
	
	
	$flow = (isset($event['flow'])?$event['flow']:'');
	$action = (isset($event['action'])?$event['action']:'');
	$event_title= $flow.':'.$action;
	
	$event['bg_color'] = $bgcolor;
	
	event_set(['event_title'=>$event_title,'message'=>$event]);
	
	//check the conditions to see if publishing has started
	
	publish_decide();
	
	if(!publish_is_active())return;
	
	output_decide();

}

\aw2_library::add_service('live_debug.output.decide','Stop Publishing Events',['func'=>'output_decide','namespace'=>__NAMESPACE__]);
function output_decide($atts=null,$content=null,$shortcode=null){
	if(!is_active()) return;		

	//run code conditions
	$codes = \aw2_library::get('@live_debug.output.code');
	if(is_array($codes)){
		array_map(function($item){
			\aw2_library::parse_shortcode($item);
				
		 },$codes);
	}
	
	$starts = \aw2_library::get('@live_debug.output.conditions');

	
	
	foreach($starts as $item){

		$match=true;		

		if(isset($item['checks'])){
			foreach($item['checks'] as $check){
				 $rhs = $check['value'];	
				
				if(isset($check['event_key']))
					$lhs=\aw2_library::get('@live_debug.event.' . $check['event_key']);

				if(isset($check['debug_key']))
					$lhs=\aw2_library::get('@live_debug.' . $check['debug_key']);

				if(isset($check['env_key']))
					$lhs=\aw2_library::get($check['env_key']);

				
				if($lhs!==$rhs){
					$match=false;	
					break; // this is loop for "and" so if any condtion fails, exit the loop
				}
			}
		}
	
		if($match===true){

			output_items($item);
			break;
		}	
	}
}


\aw2_library::add_service('live_debug.publish.decide','Stop Publishing Events',['func'=>'publish_decide','namespace'=>__NAMESPACE__]);
function publish_decide($atts=null,$content=null,$shortcode=null){
	if(!is_active()) return;		
	
	$starts = \aw2_library::get('@live_debug.start.conditions');
	
	if(!is_array($starts)) return;
	
	foreach($starts as $item){
		
		$match=true;

		foreach($item['checks'] as $check){
			 $rhs = $check['value'];	
			
			if(isset($check['event_key']))
				$lhs=\aw2_library::get('@live_debug.event.' . $check['event_key']);

			if(isset($check['debug_key']))
				$lhs=\aw2_library::get('@live_debug.' . $check['debug_key']);

			if(isset($check['env_key']))
				$lhs=\aw2_library::get($check['env_key']);

			
			if($lhs!==$rhs){
				$match=false;	
				break; // this is loop for "and" so if any condtion fails, exit the loop
			}
			
		}
		
		if($match===true){
			//\util::var_dump($match);
			publish_start();
			break;
		}	 
	}
	
	//run code conditions
	$codes = \aw2_library::get('@live_debug.start.code');
	if(is_array($codes)){
		array_map(function($item){
				\aw2_library::parse_shortcode($item);
				
		 },$codes);
	}
}

function output_items($atts){
	
	extract(\aw2_library::shortcode_atts( array(
	'output'=>array()
	), $atts, '' ) );

	//after everything is verified reduce the event
	$left_events = \aw2_library::get('@live_debug.left_events');
	if($left_events <= 0) return;
	
	\aw2_library::set('@live_debug.left_events',((int)$left_events-1));
	
	//check if subscribe condition is met 
	foreach($output as $item){
		if(!isset($item['service'])) continue;
		
		$service = $item['service'];
		unset($item['service']);
		
		if(function_exists("\aw2\debug_output\\".$service )){
			call_user_func("\aw2\debug_output\\".$service ,$item);
		}

	}

}

\aw2_library::add_service('live_debug.event.set','Stop Publishing Events',['func'=>'event_set','namespace'=>__NAMESPACE__]);
function event_set($atts=null,$content=null,$shortcode=null){
	if(!is_active()) return;		
	
	extract(\aw2_library::shortcode_atts( array(
	'event_title'=>'',
	'message'=>''
	), $atts, '' ) );

	$message['event_id']=$event_title;
	
	\aw2_library::set('@live_debug.event',$message);
	\aw2_library::set('@live_debug.event_title',$event_title);

}

\aw2_library::add_service('live_debug.output.add','Output  Events On Conditions',['func'=>'output_add','namespace'=>__NAMESPACE__]);
function output_add($atts=null,$content=null,$shortcode=null){

	$ab=new \array_builder();
	$arr=$ab->parse($content);
	

	$arr['output']=array_map(function($item){
			if(isset($item['event_keys']))
				$item['event_keys'] = explode(',',$item['event_keys']);

			return $item;
	 },$arr['output']);

	\aw2_library::set('@live_debug.output.conditions.new',$arr);
}

\aw2_library::add_service('live_debug.output.code','Stop Publishing Events',['func'=>'output_code','namespace'=>__NAMESPACE__]);
function output_code($atts=null,$content=null,$shortcode=null){
	if(!is_active()) return;
	
	\aw2_library::set('@live_debug.output.code.new',$content);
}

namespace aw2\debug_output;

\aw2_library::add_service('live_debug.output.dump','publish event on the screen',['namespace'=>__NAMESPACE__]);
function dump($atts=null,$content=null,$shortcode=null){

	if(!\aw2\live_debug\is_active()) return;		
	if(!\aw2\live_debug\publish_is_active()) return;	
		
	$bg_color = \aw2_library::get('@live_debug.event.bg_color');	

	$bg_color=empty($bg_color)?'#C1EFFF':$bg_color;
		
	$active_event = \aw2_library::get('@live_debug.event_title');

	$msg='<h3>' . $active_event .
	'<br><small> app:<em>' . \aw2_library::get('app.slug') .'</em>' .
	' post_type:<em>' . \aw2_library::get('module.collection.post_type').'</em>' .
	' module:<em>' . \aw2_library::get('module.slug') .'</em>'.
	' tpl:<em>' . \aw2_library::get('template.name').'</em>' .
	' svc:<em>' . \aw2_library::get('module.collection.service_id') .'</em>'.
	' conn:<em>' . \aw2_library::get('module.collection.connection').'</em>' .
	'</small></h3>' ;
	//<event> (App:<> pt:<> m:<> t:<> svc:<>    conn:<>)
	
	if(isset($atts['event_keys'])){	
		foreach($atts['event_keys'] as $key){	
			$msg .= '<em>'.$key .'</em>'.\util::var_dump(\aw2_library::get('@live_debug.event.' . $key),true);
		}
	}
	
	if($atts['event']==='yes'){
		$msg .= '<em>#all</em>'.\util::var_dump(\aw2_library::get('@live_debug.event'),true);
	}
	
	
	echo "<template class='awesome_live_debug_data'> <div style='padding:10px;margin-bottom:5px;background-color:".$bg_color."'>".$msg."</div></template>";
	
	if($atts['env_dump']==='yes')
		echo "<template class='awesome_live_debug_data'> <div>".\aw2\env\dump([],null,null)."</div></template>";
	
	if($atts['die']==='yes'){
		die('Die Called.');
	}	
}


/*

\aw2_library::add_service('live_debug.active','Setup Debugging Env',['namespace'=>__NAMESPACE__]);
function active($atts=null,$content=null,$shortcode=null){
	if(!isset($_COOKIE['live_debug'])) return;	
	if(!defined('LIVE_DEBUG')) return 'no';
	
	return LIVE_DEBUG;
}



\aw2_library::add_service('live_debug.setup','Setup Debugging Env',['namespace'=>__NAMESPACE__]);
function setup($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;	
	
	\aw2_library::set('@live_debug',array(
		"event"=>"",
		"subscribed"=>"",
		"is_publishing"=>"no",
		"left_events"=>"500",
		"debug_button"=>"no"
	));
	
	$ticket_id = $_COOKIE['live_debug'];
	$ticket= \aw2\session_ticket\get(["main"=>$ticket_id]);
	\aw2_library::parse_shortcode($ticket['debug_code']);
}

\aw2_library::add_service('live_debug.subscribe.add','Subscribe To Events',['namespace'=>__NAMESPACE__]);
function add($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;	
	
	extract(\aw2_library::shortcode_atts( array(
	'main'=>'',
	'event'=>null,
	), $atts, '' ) );
	
	
	//$debug_env = &\aw2_library::get_array_ref('#debug');
	
	 array_map(function($item){
			\aw2_library::set('@live_debug.subscribed.'.$item,'y');
			
	 },explode(',', $event) );
	
}

\aw2_library::add_service('live_debug.publish.start','Start Publishing Events',['func'=>'publish_start','namespace'=>__NAMESPACE__]);
function publish_start($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;		
	

	
	$status = \aw2_library::get('@live_debug.is_publishing');
	if($status==='yes')return;	
	
	$event = \aw2_library::get('@live_debug.event');	
	
	echo '
		
<a href="#" id="awesome-debug-button" style="position:fixed;z-index: 10000;width:60px;height:60px;bottom:40px;left:40px;background-color:#0C9;color:#FFF;border-radius:50px;text-align:center;box-shadow: 2px 2px 3px #999;">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
	<g>
		<path d="M498.667,290.667H404V240c0-1.016,0.313-2.036,0.291-3.055C452.4,231.927,480,200.272,480,148.333v-12
			c0-7.364-5.971-13.333-13.333-13.333s-13.333,5.969-13.333,13.333v12c0,38.399-17.005,58.821-51.885,62.167
			c-6.069-27.025-20.875-50.381-45.537-55.7c-3.745-28.54-21.413-52.689-46.115-65.227c10.321-10.501,16.871-24.887,16.871-40.74V37
			c0-7.364-5.971-13.333-13.333-13.333S300,29.636,300,37v11.833C300,66.203,285.536,80,268.167,80h-23
			c-17.369,0-31.833-13.797-31.833-31.167V37c0-7.364-5.971-13.333-13.333-13.333S186.667,29.636,186.667,37v11.833
			c0,15.853,6.549,30.239,16.871,40.741c-24.701,12.537-42.453,36.687-46.199,65.227c-24.695,5.324-39.465,28.736-45.519,55.808
			c-35.759-2.96-53.153-23.403-53.153-62.276v-12c0-7.364-5.971-13.333-13.333-13.333S32,128.969,32,136.333v12
			c0,52.415,27.439,84.168,76.375,88.739C108.353,238.048,108,239.025,108,240v50.667H13.333C5.971,290.667,0,296.636,0,304
			s5.971,13.333,13.333,13.333H108v23c0,10.628,1.469,20.993,3.608,30.992C60.659,374.777,32,406.773,32,460.333v12
			c0,7.364,5.971,13.333,13.333,13.333s13.333-5.969,13.333-13.333v-12c0-41.795,20.151-62.291,61.565-62.649
			c22.451,53.208,75.151,90.649,136.435,90.649c61.276,0,113.971-37.432,136.425-90.629c40.519,0.784,60.241,21.283,60.241,62.629
			v12c0,7.364,5.971,13.333,13.333,13.333S480,479.697,480,472.333v-12c0-53.1-28.823-85.013-78.96-88.921
			c2.151-10.025,2.96-20.421,2.96-31.079v-23h94.667c7.363,0,13.333-5.969,13.333-13.333S506.029,290.667,498.667,290.667z
			 M242.667,460.855c-60.333-6.964-108-58.353-108-120.521V240c0-20.793,6.948-50.531,24.483-58.035
			c6.627,18.368,24.56,31.368,45.184,31.368h38.333V460.855z M204.333,186.667c-11.58,0-21-9.42-21-21c0-32.532,26.468-59,59-59
			h2.833h23H271c32.532,0,59,26.468,59,59c0,11.58-9.42,21-21,21H204.333z M377.333,340.333c0,62.627-47.027,114.32-108,120.673
			V213.333H309c20.624,0,37.891-13,44.517-31.368c17.535,7.504,23.816,37.241,23.816,58.035V340.333z"/>
	</g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
</svg>

</a>
<script type="spa/axn" axn="core.run_script">

jQuery("a#awesome-debug-button").on("click",function(e){
	e.preventDefault(); 
	html="";
	jQuery("template.awesome_live_debug_data").each(function(){
		html += $(this).html();
	
	});
	
	var w = window.open("", "awesome-debug-window");
    $(w.document.body).html(html);

})

</script>
		
		';
	
	$msg='<strong>' . 'Debug Enabled' . '</strong><br/>' . \util::var_dump($event,true);
	echo "<template class='awesome_live_debug_data'>".$msg."</template>";
	
	\aw2_library::set('@live_debug.is_publishing',"yes");

}

\aw2_library::add_service('live_debug.publish.stop','Stop Publishing Events',['func'=>'publish_stop','namespace'=>__NAMESPACE__]);
function publish_stop($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;		
	


	$status = \aw2_library::get('@live_debug.is_publishing');
	if($status==='no')return;	

	$event = \aw2_library::get('@live_debug.event');
	
	$msg='<strong>' . 'Debug Stopped' . '</strong><br/>' . \util::var_dump($event,true);
	echo "<template class='awesome_live_debug_data'>".$msg."</template>";

	
	\aw2_library::set('@live_debug.is_publishing',"no");

}



\aw2_library::add_service('live_debug.event.raise','Stop Publishing Events',['func'=>'event_raise','namespace'=>__NAMESPACE__]);
function event_raise($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;		
	
	extract(\aw2_library::shortcode_atts( array(
	'event'=>'',
	'message'=>''
	), $atts, '' ) );
	
	//\aw2_library::set('@live_debug.code.new',$content);
	
	event_add(['event'=>$event,'message'=>$message]);
	publish_decide([]);
	publish_event($atts);
	
}

\aw2_library::add_service('live_debug.event.add','Stop Publishing Events',['func'=>'event_add','namespace'=>__NAMESPACE__]);
function event_add($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;		
	
	extract(\aw2_library::shortcode_atts( array(
	'event'=>'',
	'message'=>''
	), $atts, '' ) );

	$message['event_id']=$event;
	
	\aw2_library::set('@live_debug.event',$message);
	\aw2_library::set('@live_debug.event_title',$event);

}

\aw2_library::add_service('live_debug.publish.decide','Stop Publishing Events',['func'=>'publish_decide','namespace'=>__NAMESPACE__]);
function publish_decide($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;		
	
	$starts = \aw2_library::get('@live_debug.start');
	
	//\util::var_dump($message['event_id']);

	
	foreach($starts as $item){
		
		$match=true;

		foreach($item['checks'] as $check){
			 $rhs = $check['value'];	
			
			if(isset($check['event_key']))
				$lhs=\aw2_library::get('@live_debug.event.' . $check['event_key']);

			if(isset($check['debug_key']))
				$lhs=\aw2_library::get('@live_debug.' . $check['debug_key']);

			if(isset($check['env_key']))
				$lhs=\aw2_library::get($check['env_key']);

			
			if($lhs!==$rhs){
				$match=false;	
				break; // this is loop for "and" so if any condtion fails, exit the loop
			}
			
		}
		
		if($match===true){
			//\util::var_dump($match);
			publish_start();
			break;
		}	 
	}
	
	//run code conditions
	$codes = \aw2_library::get('@live_debug.code');
	array_map(function($item){
			\aw2_library::parse_shortcode($item);
			
	 },$codes);
	
}

\aw2_library::add_service('live_debug.publish.event','Stop Publishing Events',['func'=>'publish_event','namespace'=>__NAMESPACE__]);
function publish_event($atts=null,$content=null,$shortcode=null){
	if(active()!=='yes') return;		
	//is it subscribed. if no then exit
	
	$is_publishing = \aw2_library::get('@live_debug.is_publishing');
	if($is_publishing !== 'yes') return;
	
	$left_events = \aw2_library::get('@live_debug.left_events');
	if($left_events <= 0) return;
	
	$active_event = \aw2_library::get('@live_debug.event_title');
	$subscribed = \aw2_library::get('@live_debug.subscribed.'.$active_event);
	if($subscribed !=='y') return;
	
	
	\aw2_library::set('@live_debug.left_events',((int)$left_events-1));
	
		
	$msg='<strong>' . $atts['event'] . '</strong><br/>' . \util::var_dump($atts['message'],true);
	echo "<template class='awesome_live_debug_data'>".$msg."</template>";
}

[live_debug.subscribe.add] 
    [checks new event_key=flow value=sc /] 
    [checks new event_key=action value=sc.found /] 
    [subscribe key='#all' /] 
    [subscribe key='hash,atts' /] 
    [output new service='live_debug.subscribed.echo' /]
[/live_debug.subscribe.add]

*/
