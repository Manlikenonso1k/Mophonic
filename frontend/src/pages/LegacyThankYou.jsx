import { Navigate, useSearchParams } from 'react-router-dom'

/** Keeps links issued before the move to /order/{reference} working. */
export default function LegacyThankYou() {
  const [params] = useSearchParams()
  const reference = params.get('reference')

  return <Navigate to={reference ? `/order/${encodeURIComponent(reference)}` : '/shop'} replace />
}
