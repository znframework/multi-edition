<?php namespace ZN\Image;
/**
 * ZN PHP Web Framework
 * 
 * "Simplicity is the ultimate sophistication." ~ Da Vinci
 * 
 * @package ZN
 * @license MIT [http://opensource.org/licenses/MIT]
 * @author  Ozan UYKUN [ozan@znframework.com]
 */

use stdClass;
use ZN\Base;
use ZN\Request;
use ZN\Singleton;
use ZN\Filesystem;
use ZN\Image\Exception\ImageNotFoundException;
use ZN\Image\Exception\InvalidImageFileException;

class Render implements RenderInterface
{
    /**
     * Thumbs directory name
     * 
     * @var string
     */
    protected $dirName = 'thumbs';
    
    /**
     * Keeps file path
     * 
     * @var string
     */
    private $file;

    /**
     * Thumb file path
     * 
     * @var string
     */
    protected $thumbPath;

    /**
     * Get prosize
     * 
     * @param string $path
     * @param int    $width = 0
     * @param int    $height = 0
     * 
     * @return object
     */
    public function getProsize(String $path, Int $width = 0, Int $height = 0) : stdClass
    {
        # If the image file is not found, an exception is thrown.
        $this->throwExceptionImageFileIfNotExists($path);

        # Get image size.
        $getImageCoordinate = getimagesize($path);

        # It gives the width and height value proportional to the width value of the picture.
        $x = $getImageCoordinate[0];
        $y = $getImageCoordinate[1];

        CoordinateRateCalculator::run($width, $x, $y);

        # It gives the width and height value proportional to the height value of the picture.
        CoordinateRateCalculator::run($height, $x, $y);

        # Return width & height
        return (object) 
        [
            'width'  => round($x),
            'height' => round($y)
        ];
    }

    /**
     * Thumb
     * 
     * @param string $fpath
     * @param array  $set
     * 
     * @return string
     */
    public function thumb(String $fpath, Array $set) : String
    {
        # If the image file is not found, an exception is thrown.
        $this->throwExceptionImageFileIfNotExists($filePath = $this->cleanURLFix($fpath));

        # If the file is not a valid image file, an exception is thrown.
        $this->throwExceptionIsNotImageFile($filePath);

        # It extracts the settings made on the image as variables.
        extract($this->extractSettingVariables($fpath, $set));

        $this->setThumbPaths($filePath);

        # If the Thumb array does not exist, it is created.
        $this->createThumbDirectoryIfNotExists();

        # If the same operation is applied before, the image is not rebuilt.
        if( $this->isThumbFileExists($getThumbFilePath = $this->getThumbFilePath($this->getThumbFileName($x, $y, $width, $height))) )
        {
            return $this->getThumbFileURL($getThumbFilePath);
        }

        $createNewImage = imagecreatetruecolor($width, $height);

        # If the extension of the image file is png, the background is transparent.
        if( $this->isPNGExtension($filePath) )
        {
            $this->applyBackgroundTransparency($createNewImage, $width, $height);
        }

        # The image is being resized according to the settings made.
        imagecopyresampled($createNewImage, $createNewImageByType = $this->fromFileType($filePath),  0, 0, $x, $y, $width, $height, $rWidth, $rHeight);

        # Creating a new image based on the file type.
        $this->createFileType($createNewImage, $getThumbFilePath, $quality);

        # The created images are being deleted.
        $this->deleteCreatedImages($createNewImageByType, $createNewImage);

        # The new image path is returned from the URL type.
        return $this->getThumbFileURL($getThumbFilePath);
    }

    /**
     * Protected throw exception image file if not exists
     */
    protected function throwExceptionIsNotImageFile($file)
    {
        if( ! $this->isImageFile($file) )
        {
            throw new InvalidImageFileException(NULL, $file);
        }
    }

    /**
     * Protected throw exception image file if not exists
     */
    protected function throwExceptionImageFileIfNotExists($file)
    {
        if( ! file_exists($file) )
        {
            throw new ImageNotFoundException(NULL, $file);
        }
    }

    /**
     * Protected delete created images
     */
    protected function deleteCreatedImages(...$images)
    {
        foreach( $images as $image )
        {
            imagedestroy($image);
        }
    }

    /**
     * Protected get thumb file url
     */
    protected function getThumbFileURL($file)
    {
        return Request::getBaseURL($file);
    }

    /**
     * Protected is thumb file exists
     */
    protected function isThumbFileExists($file)
    {
        return file_exists($file);
    }

    /**
     * Protected get thumb file path
     */
    protected function getThumbFilePath($file)
    {
        return $this->thumbPath . $file;
    }

    /**
     * Protected get thumb file name
     */
    protected function getThumbFileName($x, $y, $width, $height)
    {
        return Filesystem::removeExtension($this->file)                 .
               $this->addPrefixToThumbFileName($x, $y, $width, $height) . 
               Filesystem::getExtension($this->file, true);
    }
    
    /**
     * Protected add prefix to thumb file name
     */
    protected function addPrefixToThumbFileName($x, $y, $width, $height)
    {
        return '-' . $x . 'x' . $y . 'px-' . $width . 'x' . $height . 'size';
    }

    /**
     * Protected create thumb directory if not exists
     */
    protected function createThumbDirectoryIfNotExists()
    {
        if( ! is_dir($this->thumbPath) )
        {
            mkdir($this->thumbPath);
        }
    }

    /**
     * Protected is png extension
     */
    protected function isPNGExtension($file)
    {
        return Filesystem::getExtension($file) === 'png';
    }

    /**
     * Protected apply bacground transparency
     */
    protected function applyBackgroundTransparency($file, $width, $height)
    {       
        imagealphablending($file, false);
        imagesavealpha($file, true);
        imagefilledrectangle($file, 0, 0, $width, $height, $this->transparentBackground($file));
    }

    /**
     * Protected transparent background
     */
    protected function transparentBackground($file)
    {
        return imagecolorallocatealpha($file, 255, 255, 255, 127);
    }

    /**
     * Protected New Path
     */
    protected function setThumbPaths($file)
    {
        $this->file      = $this->getOnlyFileName($file);
        $this->thumbPath = $this->createThumbDirectory($file);
    }

    /**
     * Protected get only file name
     */
    protected function getOnlyFileName($file)
    {
        return pathinfo($file, PATHINFO_BASENAME);
    }

    /**
     * Protected get only directory name
     */
    protected function getFileDirectory($file, $thumb = NULL)
    {
        return pathinfo($file, PATHINFO_DIRNAME) . '/';
    }

    /**
     * Protected get thumb directory name
     */
    protected function getThumbDirectoryName()
    {
        return Base::suffix($this->dirName);
    }

    /**
     * Protected clean url fix
     */
    protected function cleanURLFix($path)
    {
        return Base::removePrefix($path, Request::getBaseURL());
    }

    /**
     * Protected create thumb directory
     */
    protected function createThumbDirectory($file)
    {
        return $this->cleanURLFix($this->getFileDirectory($file) . $this->getThumbDirectoryName());
    }

    /**
     * Protected From File Type
     */
    protected function fromFileType($paths)
    {
        switch( Filesystem::getExtension($this->file) )
        {
            case 'jpg' :
            case 'jpeg': return imagecreatefromjpeg($paths);
            case 'png' : return imagecreatefrompng ($paths);
            case 'gif' : return imagecreatefromgif ($paths);
            default    : return false;
        }
    }

    /**
     * Protected Is Image File
     */
    protected function isImageFile($file)
    {
        $mimes = ['image/jpg', 'image/jpeg', 'image/png', 'image/gif'];

        if( in_array(Singleton::class('ZN\Helpers\Mime')->type($file), $mimes) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Protected Create File Type
     */
    protected function createFileType($files, $paths, $quality = 0)
    {
        switch( Filesystem::getExtension($this->file) )
        {
            case 'jpg' :
            case 'jpeg': return imagejpeg($files, $paths, $quality ?: 80);
            case 'png' : 
                if( $quality > 10 )
                {
                    $quality = (int) ($quality / 10);
                }
                return imagepng($files, $paths, $quality ?: 8 );
            case 'gif' : return imagegif($files, $paths);
            default    : return false;
        }
    }

    /**
     * Protected extract setting variables
     */
    protected function extractSettingVariables($file, $settings)
    {
        $variables = [];

        list($currentWidth, $currentHeight) = getimagesize($file);

        $variables['x']         = $settings['x']         ?? 0;
        $variables['y']         = $settings['y']         ?? 0;
        $variables['quality']   = $settings['quality']   ?? 0;
        $variables['prowidth']  = $settings['prowidth']  ?? NULL;
        $variables['proheight'] = $settings['proheight'] ?? NULL;
        $rewidth                = $settings['width']     ?? $currentWidth;
        $reheight               = $settings['height']    ?? $currentHeight;

        # Resizes the height value.
        if( ! empty($settings['reheight' ]) ) 
        {
            $height = $settings['reheight'];
        } 
        # It gives the width and height value proportional to the height value of the picture.
        elseif( ! empty($settings['proheight']) && $settings['proheight'] < $currentHeight )
        {
            $height = $settings['proheight'];
            $width  = round(($currentWidth * $height) / $currentHeight);    
        }  
        
        # Resizes the width value.
        if( ! empty($settings['rewidth'  ]) ) 
        {
            $width = $settings['rewidth' ];
        }
        # It gives the width and height value proportional to the width value of the picture.
        elseif( ! empty($settings['prowidth']) && $settings['prowidth'] < $currentWidth )
        {
            $width  = $settings['prowidth'];
            $height = round(($currentHeight * $width) / $currentWidth);   
        }

        # Gets width and height value information.
        $variables['width' ] = $width  ?? $rewidth;
        $variables['height'] = $height ?? $reheight;

        # The black portions are cut off.
        $variables['rWidth']  = $rewidth  - $variables['x'];
        $variables['rHeight'] = $reheight - $variables['y'];

        # Return setting variables.
        return $variables;
    }
}
