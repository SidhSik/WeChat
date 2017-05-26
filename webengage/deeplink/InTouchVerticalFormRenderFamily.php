<?php 

/**
 * 
 * @author prakhar
 *
 *Creats In Touch Style Vertical Form 
 */

class InTouchVerticalFormRenderFamily{
        
        private $table_align;	
	private $org_id;
	private $form_widget;
	private $button_name;
	private $remove_header_footer;
	
	/**
	 * Constructor of the form..
	 * 
	 * @param WIDGET $form_widget
	 */
	public function InTouchVerticalFormRenderFamily( $form_widget, $button_name = 'Submit' , $remove_header_footer = false ){
		
		global $currentorg;
		
		$this->form_widget = $form_widget;
		$this->button_name = _common($button_name);
		$this->remove_header_footer = $remove_header_footer;
		$this->org_id = $currentorg->org_id;
	}	
	
	/**
	 * Constructs form footer
	 * 
	 * @param $str
	 */
	public function constructFormFooter( $str ){

		$field_name = $this->form_widget->getFormName();
		$submit_enabled = $this->form_widget->isSubmitEnabled();
		$on_click = $this->form_widget->getSubmitOnClickAction();
		
		if( $on_click ){

			$type = 'button';
			$class = "class = 'btn1 btn-primary'";
		}else{
			$type = 'submit';
			$class = "class = 'btn1 btn-primary'";
		}
			
		$str.= "<input type='hidden' name='$field_name"."__is_form_submitted' value='true' />
				<input type='hidden' name='$field_name"."__org_id' value='".$this->org_id."' />";

		if ( $submit_enabled ) {
			
			$style = '';
			if( $this->remove_header_footer )
				$style=" align = 'right'";
				
			$str .= "<tr>
					<td  colspan='2' $style>
					<input id = '".$field_name."__submit' type='$type' name = 'submit' value='$this->button_name' $on_click $class />
					</td></tr>";
			
			#  --> doesn't work in IE. Modify:
		}
		$str .= "</tbody></table></form>";
		$str .= "</div>";
		
		return $str;
	}
	
	/**
	 * Returns the html for the form
	 */
	public function getHtml(){
		include_once 'module/template/campaigns/v3/deeplink.tpl';

		$div_name = $this->form_widget->getDivName();
		$enctype = $this->form_widget->getEnctype();
		$method = $this->form_widget->getFormMethod();
		$action = $this->form_widget->getFormAction();
		$input_fields = $this->form_widget->getInputField();
		$form_name = $this->form_widget->getFormName();
		$style = $this->form_widget->getCssStyle();
		$step_name = $this->form_widget->getStepName();
		if( $action )
			$action .= '&step_name='.$step_name;
		
		$str = "<div id = '$div_name'  >";
		$str .=	"<form id='$form_name' $enctype action = '$action' method='$method' >";
			
		$str.= "<table id = '$style' $this->table_align >";
		
		if( !$this->remove_header_footer )
		$str.=" <thead>
				<tr>
					<th scope='col' class='rounded-company'>&nbsp;</th>
					<th scope='col' class='rounded-q4'>&nbsp;</th>
				</tr>
				</thead> 
				<tfoot>
					<tr>
						<td class='rounded-foot-left'>&nbsp;</td>
	        			<td class='rounded-foot-right'>&nbsp;</td>
					</tr>
				</tfoot>";
		
		$str.="<tbody>";
		
		foreach ( $input_fields as $field ) {
			
			$field_html = $field->getHtml();
			$field_type = $field->getFieldType();
			$field_label = $field->getLabel();
			$field_name = $field->getName();
			$helptext = $field->getHelpText();
			$error_code = $field->getErrorCode();

			if( $field_type == 'hidden' ){
				
				$str .= $field_html;
				continue;
			}

			if ( $field_label ){
				if($field->isMandatory()){
					$field_label = $field_label . "<span class=\"mandatory\"> * </span>";
				}
				
				$str .= "\t<tr>\n\t\t<td>$field_label</td>\n\t\t<td>";
			}
				
			$str .= $field_html;
			
			if( $helptext )
				$str .= "<br/><small class='text-info'>$helptext</small>";
				
			if( $error_code )
				$str .= "<small class='error'>$error_code</small>";

			if( strcasecmp($field_label, 'IOS')==0 ){
				echo "PHPPPP";
				$str .= "<tr>
							<td>Deep Link</td>
							<td>
								<div id='deeplink_1' class='add_deep_links'>+ Add Deep Links</div>
							</td>
						</tr>";
			}

		}
				
		$str = $this->constructFormFooter( $str );

		return $str;
	}
        
        /**
         *Adding Table Alignment method to center a table or left by default.
         * @param type $align 
         */
        public function setTableAlign( $align ){
            $this->table_align = "align='$align'";
	}
}

?>
