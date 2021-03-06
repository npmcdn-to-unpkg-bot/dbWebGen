<?
	//==========================================================================================
	class dbWebGenChart_bar extends dbWebGenChart_Google {
	//==========================================================================================
		// select name "Student", num_changes "Number of Edits" from view_changes_by_user v, users u where role = 'user' and u.id = v.user_id order by 2 desc
		
		// select lastname_translit "Persons by Family Name", count(*)::int "Number of Persons", max(length(forename_translit))::int "Longest Name" from persons group by 1 order by 2 desc limit 10
		
		//--------------------------------------------------------------------------------------
		// form field @name must be prefixed with exact charttype followed by dash
		public function settings_html() { 
		//--------------------------------------------------------------------------------------
			return <<<HTML
			<p>Renders data as a bar chart. Put group labels in the 1st column, followed by one column per group listing the data (<a target="_blank" href="https://developers.google.com/chart/interactive/docs/gallery/barchart#data-format">see details</a>).</p>
			<div class="form-group">
				<label class="control-label">Bar Direction</label>
				<div>
					<label class="radio-inline">{$this->page->render_radio('bar-direction', 'horizontal', true)}Horizontal</label>
					<label class="radio-inline">{$this->page->render_radio('bar-direction', 'vertical')}Vertical</label>
				</div>
			</div>
			<!-- STACKED DOES NOT WORK !<div class="form-group">
				<label class="control-label">Stacking of Values</label>
				<div>
					{$this->page->render_select('bar-stacking', 0, array(
						0 => 'None',
						'absolute' => 'Absolute values',
						'percent' => 'Relative values as a percentage of 100%',
						'relative' => 'Relative values as a fraction of 1'						
					))}
				</div>
			</div>-->
HTML;
		}
		
		//--------------------------------------------------------------------------------------
		// need to override this because of material options conversion
		public function before_draw_js() {
		//--------------------------------------------------------------------------------------
			return 'options = google.charts.Bar.convertOptions(options);';
		}
		
		//--------------------------------------------------------------------------------------
		protected function options() {			
		//--------------------------------------------------------------------------------------
			return parent::options() + array(
				'bars' => $this->page->get_post('bar-direction'),
				'isStacked' => $this->page->get_post('bar-stacking')
			);
		}
		
		//--------------------------------------------------------------------------------------
		// return google charts class name to instantiate
		public function class_name() {
		//--------------------------------------------------------------------------------------
			return 'google.charts.Bar';
		}
		
		//--------------------------------------------------------------------------------------
		// return google charts packages to include
		public function packages() {
		//--------------------------------------------------------------------------------------
			return array('bar');
		}
	};
?>