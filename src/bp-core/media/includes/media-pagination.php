<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BuddyBoss_Media_Paginated' ) ):

class BuddyBoss_Media_Paginated
{
  private $rs;                            //result set
  private $pageSize;                      //number of records to display
  private $pageNumber;                    //the page to be displayed
  private $rowNumber;                     //the current row of data which must be less than the pageSize in keeping with the specified size
  private $offSet;
  private $layout;

  function __construct( $obj, $displayRows = 10, $pageNum = 1 )
  {
    $this->setRs( $obj );
    $this->setPageSize( $displayRows );
    $this->assignPageNumber( $pageNum );
    $this->setRowNumber( 0 );
    $this->setOffSet( ( $this->getPageNumber() - 1 ) * ( $this->getPageSize() ) );
  }

  //implement getters and setters
  public function setOffSet( $offSet )
  {
    $this->offSet = $offSet;
  }

  public function getOffSet()
  {
    return $this->offSet;
  }


  public function getRs()
  {
    return $this->rs;
  }

  public function setRs( $obj )
  {
    $this->rs = $obj;
  }

  public function getPageSize()
  {
    return $this->pageSize;
  }

  public function setPageSize( $pages )
  {
    $this->pageSize = $pages;
  }

  //accessor and mutator for page numbers
  public function getPageNumber()
  {
    return $this->pageNumber;
  }

  public function setPageNumber( $number )
  {
    $this->pageNumber = $number;
  }

  //fetches the row number
  public function getRowNumber()
  {
    return $this->rowNumber;
  }

  public function setRowNumber( $number )
  {
    $this->rowNumber = $number;
  }

  public function fetchNumberPages()
  {
    if ( !$this->getRs() )
    {
      return false;
    }

    $pages = ceil( count( $this->getRs() ) / (float) $this->getPageSize() );
    return $pages;
  }

  //sets the current page being viewed to the value of the parameter
  public function assignPageNumber($page) {
    if(($page <= 0) || ($page > $this->fetchNumberPages()) || ($page == "")) {
      $this->setPageNumber(1);
    }
    else {
      $this->setPageNumber($page);
    }
    //upon assigning the current page, move the cursor in the result set to (page number minus one) multiply by the page size
    //example  (2 - 1) * 10
  }

  public function fetchPagedRow()
  {
    if( ( !$this->getRs() ) || ( $this->getRowNumber() >= $this->getPageSize() ) )
    {
      return false;
    }

    $this->setRowNumber( $this->getRowNumber() + 1 );
    $index = $this->getOffSet();
    $this->setOffSet( $this->getOffSet() + 1 );
    return $index;
  }

  public function isFirstPage()
  {
    return ( $this->getPageNumber() <= 1 );
  }

  public function isLastPage()
  {
    return ( $this->getPageNumber() >= $this->fetchNumberPages() );
  }

  public function fetchPagedLinks($parent, $queryVars)
  {
    $currentPage = $parent->getPageNumber();
    $str = "<nav class='navigation paging-navigation photos-navigation' role='navigation'>";
    $str .= "<div class='pagination loop-pagination buddyboss-media-pagination'>";

	/**
	 * ?page=2 etc will only work under user profile
	 * if we are on all media 'page', the querystring parameter scheme wont work becuase of wordpress' url rewrite.
	 * e.g: http://domain.com/all-media?page=n is automaticaly redirected to http://domain.com/all-media/n/
	 * so we need to support two schemes : ?page=n and /page/n
	 * 
	 * temporary fix
	 */
	$pagination_param_scheme = 'qs';//querystring parameter ?page=n
	if( !bp_is_user() && is_page() ){
		$pagination_param_scheme = 'paged';// /page/n

		$page_permalink = untrailingslashit( get_permalink( get_the_ID() ) );
		
		$queryVars_paged = $queryVars;
		//in this case, $queryVars (which starts with '&') should also be modified
		//lets remove the first '&'
		if ( ( strpos( $queryVars, '&') === 0 ) ) {
			$queryVars_paged = substr($queryVars, 1);
		}
	}
	
    if( !$parent->isFirstPage() )
    {
      if( $currentPage != 1 && $currentPage != 2 && $currentPage != 3 )
      {
		if( 'paged' != $pagination_param_scheme ){
			$str .= "<a href='?page=1$queryVars' title='Start' class='pag-first page-numbers'>". __( 'First' , 'buddyboss-media' ) . " (1)</a>";
		}
		else{
			$str .= "<a href='{$page_permalink}/?{$queryVars_paged}' title='Start' class='pag-first page-numbers'>". __( 'First' , 'buddyboss-media' ) . " (1)</a>";
		}
      }
    }

    for( $i = $currentPage - 2; $i <= $currentPage + 2; $i++ )
    {
      //if i is less than one then continue to next iteration
      if( $i < 1 )
      {
        continue;
      }

      if( $i > $parent->fetchNumberPages() )
      {
        break;
      }

      if( $i == $currentPage )
      {
        $str .= "<span class='page-numbers current'>$i</span>";
      }
      else {
		  if( 'paged' != $pagination_param_scheme ){
			$str .= "<a class='page-numbers pag-page' href=\"?page=$i$queryVars\">$i</a>";
		  }
		  else{
			  $str .= "<a class='page-numbers pag-page' href=\"{$page_permalink}/page/{$i}/?{$queryVars_paged}\">$i</a>";
		  }
      }

      if ( $currentPage != $parent->fetchNumberPages() || $i != $parent->fetchNumberPages() )
        $str .= '';

    }//end for

    if ( !$parent->isLastPage() )
    {
      if( $currentPage != $parent->fetchNumberPages() && $currentPage != $parent->fetchNumberPages() -1 && $currentPage != $parent->fetchNumberPages() - 2 )
      {
		  if( 'paged' != $pagination_param_scheme ){
			$str .= "<a class='pag-last page-numbers' href=\"?page=".$parent->fetchNumberPages()."$queryVars\" title=\"Last\">" . __( 'Last' , 'buddyboss-media' ) . " (".$parent->fetchNumberPages().")</a>";
		  }
		  else{
			$str .= "<a class='pag-last page-numbers' href=\"{$page_permalink}/page/".$parent->fetchNumberPages()."/?$queryVars\" title=\"Last\">" . __( 'Last' , 'buddyboss-media' ) . " (".$parent->fetchNumberPages().")</a>";
		  }
      }
    }

    $str .= "</div>";
    $str .= "</nav>";

    return $str;
  }

  public function fetchPagedNavigation( $queryVars = "" )
  {
    if ( count( $this->getRs() ) > $this->getPageSize() )
      return $this->fetchPagedLinks( $this, $queryVars );
  }

} // end BuddyBoss_Media_Paginated

endif;

?>