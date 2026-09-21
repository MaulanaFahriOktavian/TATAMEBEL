import React from 'react';

/**
 * QcStatusCard component providing reassuring quality assurance status.
 */
export default function QcStatusCard({ qc }) {
  if (!qc) return null;

  const isPassed = qc.status === 'PASSED';
  const isInProgress = qc.status === 'IN_PROGRESS';

  let iconVariant = 'pending';
  let iconSymbol = '📋';

  if (isPassed) {
    iconVariant = 'passed';
    iconSymbol = '✓';
  } else if (isInProgress) {
    iconVariant = 'in_progress';
    iconSymbol = '🔍';
  }

  const passedDateStr = isPassed && qc.passed_at
    ? new Date(qc.passed_at).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      })
    : null;

  return (
    <div className="portal-card">
      <div className="portal-card-header">
        <span className="portal-card-title">Standar Kualitas (QC)</span>
      </div>

      <div className="qc-box">
        <div className={`qc-icon-box ${iconVariant}`}>
          <span>{iconSymbol}</span>
        </div>
        <div className="qc-details">
          <div className="qc-status-headline">{qc.status_label || qc.status}</div>
          <div className="qc-status-note">{qc.note}</div>
          {passedDateStr && (
            <div className="qc-timestamp">Diverifikasi pada: {passedDateStr}</div>
          )}
        </div>
      </div>
    </div>
  );
}
