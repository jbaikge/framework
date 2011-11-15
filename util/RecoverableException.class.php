<?php
/*!
 * Recoverable Exception to go along side the E_RECOVERABLE_ERROR which
 * typically gets thrown when trying to pass an invalid value to a type-hinted
 * method or function.
 *
 * @author Jake Tews <jtews@okco.com>
 * @date Tue Nov 15 08:39:36 EST 2011
 */
class RecoverableException extends Exception {
}
