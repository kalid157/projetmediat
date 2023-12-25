/**
 * External dependencies
 */
import classnames from "classnames";
import MaterialUiIconPicker from 'react-material-ui-icon-picker';
import icons from './icon.js';

/**
 * BLOCK: Gosign- Advanced Separator Block
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
import './style.scss';
import './editor.scss';

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks



const {
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
	Dashicon,
	IconButton,
	Button,
	Toolbar,
	PanelBody,
	RadioControl,
	AccessibleSVG,
	TabPanel,
} = wp.components
const {
	InspectorControls,
	BlockControls,
	ColorPalette,
	AlignmentToolbar,
	RichText,
	URLInput,
	MediaUpload,
} = wp.editor.InspectorControls ? wp.editor : wp.blocks
const {
	PanelColorSettings,
	BlockAlignmentToolbar,
	InnerBlocks,
} = wp.editor
const {
	Fragment,
} = wp.element

/**
 * Register: aa Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */
registerBlockType( 'gosign/block-gosign-advanced-separator-block', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'Gosign - Advanced Separator Block' ), // Block title.
	icon: icons.separator, // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'common', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'gosign-advanced-separator-block — gosign Block' ),
		__( 'separator' ),
		__( 'Whitespace' ),
	],
	attributes: {
		separator: {
			type: 'string',
			default: 'default'
		},
		fullWidthSep: {
			type: 'string',
			default: 'no-shadow'
		},
		shortSep: {
			type: 'string',
			default: 'center'
		},
		customSepPos: {
			type: 'string',
			default: 'center'
		},
		customBorderSz: {
			type: 'string',
			default: 'custBorderThin'
		},
		customWidthOfBorder: {
			default: 20,
			type: 'int'
		},
		customWidthOfBorder2: {
			default: 20,
			type: 'number'
		},
		customWidthOfImageIcon: {
			default: 15+'px',
			type: 'int'
		},
		insideCustomHeight: {
			default: 0,
			type: 'int'
		},
		custHeightBorderPX: {
			default: 1,
			type: 'int'
		},
		customSizeIcon: {
			default: 10,
			type: 'int'
		},
		align: {
			type: 'string',
			default: 'full'
		},
		marginTopSepCus: {
			default: 0,
			type: 'number'
		},
		marginBottomSepCus: {
			default: 0,
			type: 'number'
		},
		marginTopSep: {
			default: 0,
			type: 'number'
		},
		marginBottomSep: {
			default: 0,
			type: 'number'
		},
		borderColorCus: {
			type: 'string',
			default: '#000000'
		},
		iconColorCus: {
			type: 'string',
			default: '#000000'
		},
		iconName: {
			type: 'string',
			default: ''
		},
		iconCode: {
			type: 'string',
			default: ''
		},
		mediaURL: {
			type: 'string',
			default: ''
		},
		mediaID: {
			type: 'int'
		},
		borderCornorRound: {
			type: 'boolean',
			default: false
		},
		customBorderIcon: {
			type: 'boolean',
			default: false
		},
		toggleControlIcon: {
			default: false,
			type: 'boolean'
		}

	},
	getEditWrapperProps(attributes) {
		const { align } = attributes;
		if (
			"left" === align ||
			"right" === align ||
			"wide" === align ||
			"full" === align
		) {
			return { "data-align": align };
		}
	},
	/**
	 * The edit function describes the structure of your block in the context of the editor.
	 * This represents what the editor will render when the block is used.
	 *
	 * The "edit" property must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */
	edit: function( props ) {

		const { customSizeIcon, customWidthOfBorder2, customWidthOfImageIcon, toggleControlIcon, mediaURL, mediaID, borderCornorRound, custHeightBorderPX, iconName, iconCode, separator, align, marginTopSep, marginBottomSep, fullWidthSep, insideCustomHeight, shortSep, customSepPos,
		customBorderIcon, customBorderSz, customWidthOfBorder, marginTopSepCus, marginBottomSepCus, borderColorCus, iconColorCus } = props.attributes;
		const {setAttributes} = props;

		const onSelect = ( tabName ) => {};


		const onSelect2 = ( tabName2 ) => {
			props.setAttributes({
				mediaURL: "",
				mediaID: ""
			})
		};
		function onIconSelect(media) {
			props.setAttributes({
				mediaURL: media.url,
				mediaID: media.id,
			});
			emptymedia2();
		}
		function showPickedIcons(icon) {
			props.setAttributes({
				iconName: icon.name,
				iconCode: icon.code,
			});
			emptyIcon();
		}

		function emptyIcon(){
			setAttributes({mediaURL:"",mediaID:""});
		}
		function emptymedia2(){
			setAttributes({iconName:"",iconCode:""});
		}
/*		function showPickedIcons(){
			setAttributes({mediaURL:"",mediaID:""});
		}*/
/*		function showPickedIcons(icon) {
		}*/

/*		const showPickedIcon = (icon) => {
			props.setAttributes()
		}*/
		// Creates a <p class='wp-block-gosign-block-gosign-advanced-separator-block'></p>.
		return (
			<div className='gosign-advanced-separator-block' >
				<BlockControls>
					<BlockAlignmentToolbar
						value={align}
						onChange={nextAlign => {
							props.setAttributes({ align: nextAlign });
						}}
						controls={[ "wide", "full"]}
					/>
				</BlockControls>
				<InspectorControls>
					<PanelBody
							title="Advanced Separator"
							icon="category"
							initialOpen={true}
							className="gosign-advanced-separator-block"
						>
						<Fragment>
							<SelectControl
								label="Choose Border Type"
								value={separator}
								options={[
									{ label: "Default", value: "default" },
									{ label: "Big Top and Bottom Margins", value: "big" },
									{ label: "Fullwidth Separator", value: "full" },
									{ label: "Whitespace", value: "invisible" },
									{ label: "Short Separator", value: "short" },
									{ label: "Custom", value: "custom" }
								]}
								onChange={separator => {
									setAttributes({ separator });
								}}
							/>
							{separator=="big" &&
								<Fragment>
								<RangeControl
									label={__('Margin Top')}
									value={marginTopSep}
									min='0'
									max='200'
									step='1'
									onChange={function( marginTopSep ) {
										props.setAttributes( { marginTopSep: marginTopSep } );
									}}
								/>
								<RangeControl
									label={__('Margin Bottom')}
									value={marginBottomSep}
									min='0'
									max='200'
									step='1'
									onChange={function( marginBottomSep ) {
										props.setAttributes( { marginBottomSep: marginBottomSep } );
									}}
								/>
								</Fragment>
							}
							{separator=="full" &&
								<Fragment>
									<SelectControl
										label="Section Top Shadow"
										value={fullWidthSep}
										options={[
											{ label: "Display shadow", value: "shadow" },
											{ label: "Do not display shadow", value: "no-shadow" }
										]}
										onChange={fullWidthSep => {
											setAttributes({ fullWidthSep });
										}}
									/>
								</Fragment>
							}
							{separator=="invisible" &&
								<Fragment>
									<TextControl
										label="Height of separator in 'px' "
										value={ insideCustomHeight }
										onChange={ ( insideCustomHeight ) => setAttributes( { insideCustomHeight } ) }
									/>
								</Fragment>
							}
							{separator=="short" &&
								<Fragment>
									<SelectControl
										label="Position"
										value={shortSep}
										options={[
											{ label: "Center", value: "center" },
											{ label: "Left", value: "left" },
											{ label: "Right", value: "right" }
										]}
										onChange={shortSep => {
											setAttributes({ shortSep });
										}}
									/>
								</Fragment>
							}
							{separator=="custom" &&
								<Fragment>

									<SelectControl
										label="Position"
										value={customSepPos}
										options={[
											{ label: "Center", value: "center" },
											{ label: "Left", value: "left" },
											{ label: "Right", value: "right" }
										]}
										onChange={customSepPos => {
											setAttributes({ customSepPos });
										}}
									/>

									<SelectControl
										label="Border"
										value={customBorderSz}
										options={[
											{ label: "none", value: "custBorderNone" },
											{ label: "thin", value: "custBorderThin" },
											{ label: "fat", value: "custBorderFat" },
											{ label: "Custom Height", value: "custHeightBorder" }
										]}
										onChange={customBorderSz => {
											setAttributes({ customBorderSz });
										}}
									/>

									{customBorderSz=="custHeightBorder" &&
										<TextControl
											label="Enter Height in px "
											value={ custHeightBorderPX }
											onChange={ ( custHeightBorderPX ) => setAttributes( { custHeightBorderPX } ) }
										/>
									}

									<ToggleControl
										label={__("Enable for round corners")}
										checked={borderCornorRound}
										onChange={borderCornorRoundVal => setAttributes({ borderCornorRound: borderCornorRoundVal })}
									/>

									<RangeControl
										label={__('Enter width in %')}
										value={customWidthOfBorder2}
										min='10'
										max='98'
										step='1'
										onChange={function( customWidthOfBorder2 ) {
											props.setAttributes( { customWidthOfBorder2: customWidthOfBorder2 } );
										}}
									/>

									<RangeControl
										label={__('Margin Top in px')}
										value={marginTopSepCus}
										min='0'
										max='200'
										step='1'
										onChange={function( marginTopSepCus ) {
											props.setAttributes( { marginTopSepCus: marginTopSepCus } );
										}}
									/>

									<RangeControl
										label={__('Margin Bottom in px')}
										value={marginBottomSepCus}
										min='0'
										max='200'
										step='1'
										onChange={function( marginBottomSepCus ) {
											props.setAttributes( { marginBottomSepCus: marginBottomSepCus } );
										}}
									/>

									<PanelColorSettings
										title={ __( 'Choose Border Color ' ) }
										initialOpen={false}
										className="borderColSettingCus"
										colorSettings={ [
											{
												value: borderColorCus,
												onChange: ( borderColorCusValue ) => props.setAttributes( { borderColorCus: borderColorCusValue } ),
												label: __( 'Select Color' ),
											},
										] }
									>

									</PanelColorSettings>
									<PanelBody
										title="Set Icon"
										icon="category"
										initialOpen={false}
										className="iconSettingCus"
									>
											<Fragment>

												<RangeControl
													label={__('Enter Icon Size its in px')}
													value={customSizeIcon}
													min='10'
													max='50'
													step='1'
													onChange={function( customSizeIcon ) {
														props.setAttributes( { customSizeIcon: customSizeIcon } );
													}}
												/>

												<TabPanel className="my-icon-panel"
													activeClass="active-tab"
													onSelect={ onSelect }
													tabs={ [
														{
															name: 'tab1',
															title: 'Pick Icon',
															className: 'tab-one',
														},
														{
															name: 'tab2',
															title: 'Upload Icon',
															className: 'tab-two',
														},
													] }>
													{
														( tab ) =>
														<Fragment>
															{
																tab.name=='tab1' &&
																<Fragment>
																	<div className="material-icons-block">
																		<MaterialUiIconPicker onPick={showPickedIcons}
																		label="Choose Icon" pickLabel="Select"
																		/>
																		<i className="material-icons">{iconName}</i>
																		{iconName &&
																			<Button className={iconName ? 'icon-button' : 'button button-large-remove'} onClick={  emptymedia2  }>
																				<div class="remove-icon">Remove Icon</div>
																			</Button>
																		}
																	</div>
																	<PanelColorSettings
																		title={ __( 'Choose Icon Color ' ) }
																		initialOpen={false}
																		colorSettings={ [
																			{
																				value: iconColorCus,
																				onChange: ( iconColorCusValue ) => props.setAttributes( { iconColorCus: iconColorCusValue } ),
																				label: __( 'Select Color' ),
																			},
																		] }
																	>
																	</PanelColorSettings>
																</Fragment>
															}
															{
																tab.name=='tab2' &&
																<Fragment>
																	<MediaUpload
																		onSelect={onIconSelect}
																		type="image"
																		value={mediaID}
																		render={( { open } ) => (
																		<Fragment>
																			<Button className={mediaID ? 'svg-button svg-button-icon' : 'svg-button button button-large'} onClick={open}>
																				{!mediaID ? __( 'Upload SVG' ) : <img src={mediaURL} />}
																			</Button>
																			{mediaID &&
																				<Button className={mediaID ? 'image-button' : 'button button-large-remove'} onClick={  emptyIcon  }>
																					{mediaID ? <div class="remove-image"> Remove Icon</div> : ' '}
																				</Button>
																			}
																			{mediaID &&
																				<Button className={mediaID ? 'image-button' : 'button button-large-replace'} onClick={  open  }>
																					{mediaID ? <div class="replace-image"> Replace Icon</div> : ' '}
																				</Button>
																			}
																		</Fragment>
																	)}
																	/>
																</Fragment>
															}
														</Fragment>
													}
												</TabPanel>
											</Fragment>
									</PanelBody>
								</Fragment>
							}
						</Fragment>
					</PanelBody>
				</InspectorControls>
				<div className={classnames('outer-sep-container', {[`sep-${separator}`]: true}, {[`cont-${align}`]: true},
					{ [`style-${fullWidthSep}`]  : separator == "full"},
					{ [`pos-${shortSep}`]  : separator == "short"},
					{ [`radius_${borderCornorRound}`]  : true},

					{ [`custPos-${customSepPos} ${customBorderSz} `]  : separator == "custom"},
					{ "customHeightSep"  : separator == "invisible"}  )}
 					style={{
 						...(separator=="custom" ? {marginTop:marginTopSepCus, marginBottom:marginBottomSepCus}:{})
 					}}>
					<div className="inner-sep-container" style={{
						...(customSizeIcon ? {height : customSizeIcon }:{})
					}}>

						<Fragment>
						{!iconName && !mediaID &&
							<div className="border-cont GAB-border" style={{
 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
 								//...(customWidthOfBorder ? {width : customWidthOfBorder }:{}),
 								...(customWidthOfBorder2 ? {width : customWidthOfBorder2+'%' }:{}),
								...(borderColorCus ? {backgroundColor : borderColorCus }:{})
							}}></div>
						}

						{iconName &&
							<Fragment>
									<div className={classnames('inner-left GAB-border')}
										style={{
											...(customWidthOfBorder2 ? {width : (customWidthOfBorder2/2)-1.5 +'%' }:{}),
			 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
											...(customSizeIcon ? {marginRight : customSizeIcon/2 +'px' }:{}),
											...(borderColorCus ? {backgroundColor : borderColorCus }:{})
			 								}}>
									</div>

									<div className="inner-mid" style={{
										...(customSizeIcon ? {height : customSizeIcon }:{}),
									}}>
										<i className="material-icons" style={{
											...(iconColorCus ? {color : iconColorCus }:{}),
											...(customSizeIcon ? {fontSize : customSizeIcon }:{}),
											...(customSizeIcon ? {lineHeight : customSizeIcon+'px' }:{}),
											...(customSizeIcon ? {height : customSizeIcon }:{}),
											...(customBorderSz=="custHeightBorder" ? {marginTop : ((custHeightBorderPX/2)-(customSizeIcon/2)) }:{}),
											...(customBorderSz=="custBorderFat" ? {marginTop : (2-(customSizeIcon/2)) }:{}),
											...(customBorderSz=="custBorderThin" ? {marginTop : (1-(customSizeIcon/2)) }:{}),
		 									...(customWidthOfBorder2 ? {marginLeft : (customWidthOfBorder2/2)-1.5 +'%',marginRight : (customWidthOfBorder2/2)-1.5 +'%' }:{})
										}}>
											{iconName}
										</i>
									</div>

									<div className={classnames('inner-right GAB-border')}
										style={{
											...(customWidthOfBorder2 ? {width : (customWidthOfBorder2/2)-1.5 +'%' }:{}),
			 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
											...(customSizeIcon ? {marginLeft : customSizeIcon/2 +'px'}:{}),
											...(borderColorCus ? {backgroundColor : borderColorCus }:{})
										}}>
									</div>
							</Fragment>
						}


						{mediaID &&
							<Fragment>
									<div className={classnames('inner-left GAB-border')}
										style={{
											...(customWidthOfBorder2 ? {width : (customWidthOfBorder2/2)-1.5 +'%' }:{}),
			 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
											...(customSizeIcon ? {marginRight : customSizeIcon/2 +'px' }:{}),
											...(borderColorCus ? {backgroundColor : borderColorCus }:{})
			 								}}>
									</div>
									<div className="inner-mid" style={{
										...(customSizeIcon ? {height : customSizeIcon }:{}),
									}}>
										<div className="iconImageB" style={{
											...(iconColorCus ? {color : iconColorCus }:{}),
											...(customSizeIcon ? {fontSize : customSizeIcon }:{}),
											...(customSizeIcon ? {lineHeight : customSizeIcon+'px' }:{}),
											...(customSizeIcon ? {height : customSizeIcon }:{}),
											...(customBorderSz=="custHeightBorder" ? {marginTop : ((custHeightBorderPX/2)-(customSizeIcon/2)) }:{}),
											...(customBorderSz=="custBorderFat" ? {marginTop : (2-(customSizeIcon/2)) }:{}),
											...(customBorderSz=="custBorderThin" ? {marginTop : (1-(customSizeIcon/2)) }:{}),
		 									...(customWidthOfBorder2 ? {marginLeft : (customWidthOfBorder2/2)-1.5 +'%',marginRight : (customWidthOfBorder2/2)-1.5 +'%' }:{})
										}}>
											<img src={mediaURL} width={customSizeIcon} />
										</div>
									</div>
									<div className={classnames('inner-right GAB-border')}
										style={{
											...(customWidthOfBorder2 ? {width : (customWidthOfBorder2/2)-1.5 +'%' }:{}),
			 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
											...(customSizeIcon ? {marginLeft : customSizeIcon/2 +'px'}:{}),
											...(borderColorCus ? {backgroundColor : borderColorCus }:{})
										}}>
									</div>
							</Fragment>
						}
						</Fragment>
					</div>
				</div>
			</div>
		);
	},

	/**
	 * The save function defines the way in which the different attributes should be combined
	 * into the final markup, which is then serialized by Gutenberg into post_content.
	 *
	 * The "save" property must be specified and must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */
	save: function( props ) {
		const { customSizeIcon, customWidthOfBorder2, customWidthOfImageIcon, toggleControlIcon, mediaID, mediaURL, borderCornorRound, custHeightBorderPX, iconName, iconCode, separator, align, marginTopSep, marginBottomSep, fullWidthSep, insideCustomHeight, shortSep, customSepPos,
		customBorderIcon, customBorderSz, customWidthOfBorder, marginTopSepCus, marginBottomSepCus, borderColorCus, iconColorCus } = props.attributes;
		const {setAttributes} = props;
		const onSelect = ( tabName ) => {};
		function emptymedia(){
			setAttributes({mediaURL:"",mediaID:""});
		}
		return (
			<div className={classnames('outer_sep', {[`sep-${separator}`]: true}, {[`cont-${align}`]: true},
				{ [`style-${fullWidthSep}`]  : separator == "full"},
				{ [`pos-${shortSep}`]  : separator == "short"},
				{ [`radius_${borderCornorRound}`]  : true},
				{ [`custPos-${customSepPos} ${customBorderSz} `]  : separator == "custom"},
				{ "customHeightSep"  : separator == "invisible"}  )}
					style={{
						...(separator=="custom" ? {marginTop:marginTopSepCus, marginBottom:marginBottomSepCus}:{}),
						...(separator == "invisible" ? {height : insideCustomHeight}:{}),
						...(separator=="big" ? {marginTop:marginTopSep, marginBottom:marginBottomSep}:{})
					}}>
				<div className="inner-sep-container" style={{
						...(customSizeIcon ? {height : customSizeIcon }:{})
					}}>
						{!iconName && !mediaID &&
							<div className="border-cont GAB-border" style={{
 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
 								...(customWidthOfBorder2 ? {width : customWidthOfBorder2+'%' }:{}),
								...(borderColorCus ? {backgroundColor : borderColorCus }:{})
							}}></div>
						}
						{iconName &&
							<Fragment>
									<div className={classnames('inner-left GAB-border')}
										style={{
											...(customWidthOfBorder2 ? {width : (customWidthOfBorder2/2)-1.5 +'%' }:{}),
			 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
											...(customSizeIcon ? {marginRight : customSizeIcon/2 +'px' }:{}),
											...(borderColorCus ? {backgroundColor : borderColorCus }:{})
			 								}}>
									</div>
									<div className="inner-mid" style={{
										...(customSizeIcon ? {height : customSizeIcon }:{}),
									}}>
										<i className="material-icons" style={{
											...(iconColorCus ? {color : iconColorCus }:{}),
											...(customSizeIcon ? {fontSize : customSizeIcon }:{}),
											...(customSizeIcon ? {lineHeight : customSizeIcon+'px' }:{}),
											...(customSizeIcon ? {height : customSizeIcon }:{}),
											...(customSizeIcon ? {marginTop : ((custHeightBorderPX/2)-(customSizeIcon/2)) }:{}),
		 									...(customWidthOfBorder2 ? {marginLeft : (customWidthOfBorder2/2)-1.5 +'%',marginRight : (customWidthOfBorder2/2)-1.5 +'%' }:{})
										}}>
											{iconName}
										</i>
									</div>
									<div className={classnames('inner-right GAB-border')}
										style={{
											...(customWidthOfBorder2 ? {width : (customWidthOfBorder2/2)-1.5 +'%' }:{}),
			 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
											...(customSizeIcon ? {marginLeft : customSizeIcon/2 +'px'}:{}),
											...(borderColorCus ? {backgroundColor : borderColorCus }:{})
										}}>
									</div>
							</Fragment>
						}
						{mediaID &&
							<Fragment>
									<div className={classnames('inner-left GAB-border')}
										style={{
											...(customWidthOfBorder2 ? {width : (customWidthOfBorder2/2)-1.5 +'%' }:{}),
			 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
											...(customSizeIcon ? {marginRight : customSizeIcon/2 +'px' }:{}),
											...(borderColorCus ? {backgroundColor : borderColorCus }:{})
			 								}}>
									</div>
									<div className="inner-mid" style={{
										...(customSizeIcon ? {height : customSizeIcon }:{}),
									}}>
										<div className="iconImageB" style={{
											...(iconColorCus ? {color : iconColorCus }:{}),
											...(customSizeIcon ? {fontSize : customSizeIcon }:{}),
											...(customSizeIcon ? {lineHeight : customSizeIcon+'px' }:{}),
											...(customSizeIcon ? {height : customSizeIcon }:{}),
											...(customBorderSz=="custHeightBorder" ? {marginTop : ((custHeightBorderPX/2)-(customSizeIcon/2)) }:{}),
											...(customBorderSz=="custBorderFat" ? {marginTop : (2-(customSizeIcon/2)) }:{}),
											...(customBorderSz=="custBorderThin" ? {marginTop : (1-(customSizeIcon/2)) }:{}),
		 									...(customWidthOfBorder2 ? {marginLeft : (customWidthOfBorder2/2)-1.5 +'%',marginRight : (customWidthOfBorder2/2)-1.5 +'%' }:{})
										}}>
											<img src={mediaURL} width={customSizeIcon} />
										</div>
									</div>
									<div className={classnames('inner-right GAB-border')}
										style={{
											...(customWidthOfBorder2 ? {width : (customWidthOfBorder2/2)-1.5 +'%' }:{}),
			 								...(custHeightBorderPX ? {height : custHeightBorderPX+'px' }:{}),
											...(customSizeIcon ? {marginLeft : customSizeIcon/2 +'px'}:{}),
											...(borderColorCus ? {backgroundColor : borderColorCus }:{})
										}}>
									</div>
							</Fragment>
						}
				</div>
			</div>
		);
	},
} );
