<?php
	/**
 * @author Gasper Kozak
 * @copyright 2007-2010

    This file is part of WideImage.

    WideImage is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 2.1 of the License, or
    (at your option) any later version.

    WideImage is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with WideImage; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    * @package Internal/Mappers
  **/

	/**
	 * Mapper class for GD2 files
	 *
	 * @package Internal/Mappers
	 */
	class WideImage_Mapper_GD2
	{
		function load($uri)
		{
			return imagecreatefromgd2($uri);
		}

		function save($handle, $uri = null, $chunk_size = null, $type = null)
		{
			imagegd2($handle, null, $chunk_size, $type);
		}
	}
